<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Infrastructure\Persistence;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Contract\CustomerRepositoryInterface;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Address;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Contact;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\CustomerStatus;
use Flexgrid\Modules\AdminCustomer\Exception\DuplicateCustomerReferenceException;

final class PdoCustomerRepository implements CustomerRepositoryInterface
{
    private const CUSTOMER_TABLE = 'admin_customer';
    private const CONTACT_TABLE = 'admin_customer_contact';
    private const ADDRESS_TABLE = 'admin_customer_address';

    /** @var \PDO */ private $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function insert(Customer $customer, int $createdAt): void
    {
        $this->assertTransaction();
        if ($createdAt < 0) {
            throw new \InvalidArgumentException('Aanmaaktijdstip is ongeldig.');
        }
        $statement = $this->connection->prepare(
            'INSERT INTO `' . self::CUSTOMER_TABLE . '` ('
            . '`tenant_id`, `public_id`, `display_name`, `company_name`, `registration_number`, '
            . '`tax_number`, `status`, `notes`, `source`, `external_id`, `created_at`, `updated_at`'
            . ') VALUES ('
            . ':tenant_id, :public_id, :display_name, :company_name, :registration_number, '
            . ':tax_number, :status, :notes, :source, :external_id, :created_at, :updated_at'
            . ')'
        );
        try {
            $statement->execute([
                ':tenant_id' => $customer->getTenantId()->toString(),
                ':public_id' => $customer->getPublicId(),
                ':display_name' => $customer->getDisplayName(),
                ':company_name' => $this->nullable($customer->getCompanyName()),
                ':registration_number' => $this->nullable($customer->getRegistrationNumber()),
                ':tax_number' => $this->nullable($customer->getTaxNumber()),
                ':status' => $customer->getStatus()->getValue(),
                ':notes' => $this->nullable($customer->getNotes()),
                ':source' => $this->nullable($customer->getSource()),
                ':external_id' => $this->nullable($customer->getExternalId()),
                ':created_at' => $createdAt,
                ':updated_at' => $createdAt,
            ]);
        } catch (\PDOException $exception) {
            if ($customer->getExternalId() !== '' && $this->isIntegrityViolation($exception)) {
                throw new DuplicateCustomerReferenceException(
                    'Externe klantreferentie bestaat al voor deze tenant.',
                    0,
                    $exception
                );
            }
            throw $exception;
        }

        $customerId = (int)$this->connection->lastInsertId();
        if ($customerId < 1) {
            throw new \RuntimeException('Database gaf geen geldige interne klant-id terug.');
        }
        $this->insertContacts($customer, $customerId, $createdAt);
        $this->insertAddresses($customer, $customerId, $createdAt);
    }

    public function findByPublicId(TenantId $tenantId, string $publicId): ?Customer
    {
        $publicId = trim($publicId);
        if ($publicId === '' || strlen($publicId) > 64) {
            throw new \InvalidArgumentException('Publieke klant-id is verplicht.');
        }

        return $this->findOne(
            '`tenant_id` = :tenant_id AND `public_id` = :public_id',
            [':tenant_id' => $tenantId->toString(), ':public_id' => $publicId]
        );
    }

    public function findByPublicIdForUpdate(TenantId $tenantId, string $publicId): ?Customer
    {
        $this->assertTransaction();
        $publicId = trim($publicId);
        if ($publicId === '' || strlen($publicId) > 64) {
            throw new \InvalidArgumentException('Publieke klant-id is verplicht.');
        }

        $statement = $this->connection->prepare(
            'SELECT * FROM `' . self::CUSTOMER_TABLE . '` '
            . 'WHERE `tenant_id` = :tenant_id AND `public_id` = :public_id LIMIT 1 FOR UPDATE'
        );
        $statement->execute([
            ':tenant_id' => $tenantId->toString(),
            ':public_id' => $publicId,
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function update(Customer $customer, int $updatedAt): void
    {
        $this->assertTransaction();
        if ($updatedAt < 0) {
            throw new \InvalidArgumentException('Wijzigingstijdstip is ongeldig.');
        }

        $lookup = $this->connection->prepare(
            'SELECT `id` FROM `' . self::CUSTOMER_TABLE . '` '
            . 'WHERE `tenant_id` = :tenant_id AND `public_id` = :public_id LIMIT 1'
        );
        $lookup->execute([
            ':tenant_id' => $customer->getTenantId()->toString(),
            ':public_id' => $customer->getPublicId(),
        ]);
        $customerId = (int)$lookup->fetchColumn();
        if ($customerId < 1) {
            throw new \RuntimeException('Klant bestaat niet meer.');
        }

        $statement = $this->connection->prepare(
            'UPDATE `' . self::CUSTOMER_TABLE . '` SET '
            . '`display_name` = :display_name, `company_name` = :company_name, '
            . '`registration_number` = :registration_number, `tax_number` = :tax_number, '
            . '`status` = :status, `notes` = :notes, `updated_at` = :updated_at '
            . 'WHERE `tenant_id` = :tenant_id AND `public_id` = :public_id'
        );
        $statement->execute([
            ':display_name' => $customer->getDisplayName(),
            ':company_name' => $this->nullable($customer->getCompanyName()),
            ':registration_number' => $this->nullable($customer->getRegistrationNumber()),
            ':tax_number' => $this->nullable($customer->getTaxNumber()),
            ':status' => $customer->getStatus()->getValue(),
            ':notes' => $this->nullable($customer->getNotes()),
            ':updated_at' => $updatedAt,
            ':tenant_id' => $customer->getTenantId()->toString(),
            ':public_id' => $customer->getPublicId(),
        ]);

        $contactCreated = $this->createdAtByPublicId(self::CONTACT_TABLE, $customer->getTenantId(), $customerId);
        $addressCreated = $this->createdAtByPublicId(self::ADDRESS_TABLE, $customer->getTenantId(), $customerId);
        foreach ([self::CONTACT_TABLE, self::ADDRESS_TABLE] as $table) {
            $delete = $this->connection->prepare(
                'DELETE FROM `' . $table . '` WHERE `tenant_id` = :tenant_id AND `customer_id` = :customer_id'
            );
            $delete->execute([
                ':tenant_id' => $customer->getTenantId()->toString(),
                ':customer_id' => $customerId,
            ]);
        }
        $this->insertContacts($customer, $customerId, $updatedAt, $contactCreated);
        $this->insertAddresses($customer, $customerId, $updatedAt, $addressCreated);
    }

    public function findByExternalReference(TenantId $tenantId, string $source, string $externalId): ?Customer
    {
        $source = strtolower(trim($source));
        $externalId = trim($externalId);
        if ($source === '' || $externalId === '') {
            return null;
        }

        return $this->findOne(
            '`tenant_id` = :tenant_id AND `source` = :source AND `external_id` = :external_id',
            [':tenant_id' => $tenantId->toString(), ':source' => $source, ':external_id' => $externalId]
        );
    }

    private function insertContacts(Customer $customer, int $customerId, int $createdAt, array $createdAtById = []): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO `' . self::CONTACT_TABLE . '` ('
            . '`tenant_id`, `public_id`, `customer_id`, `name`, `email`, `phone`, `role`, '
            . '`is_primary`, `position`, `created_at`, `updated_at`'
            . ') VALUES ('
            . ':tenant_id, :public_id, :customer_id, :name, :email, :phone, :role, '
            . ':is_primary, :position, :created_at, :updated_at'
            . ')'
        );
        foreach ($customer->getContacts() as $contact) {
            $statement->execute([
                ':tenant_id' => $customer->getTenantId()->toString(),
                ':public_id' => $contact->getPublicId(),
                ':customer_id' => $customerId,
                ':name' => $contact->getName(),
                ':email' => $this->nullable($contact->getEmail()),
                ':phone' => $this->nullable($contact->getPhone()),
                ':role' => $this->nullable($contact->getRole()),
                ':is_primary' => $contact->isPrimary() ? 1 : 0,
                ':position' => $contact->getPosition(),
                ':created_at' => $createdAtById[$contact->getPublicId()] ?? $createdAt,
                ':updated_at' => $createdAt,
            ]);
        }
    }

    private function insertAddresses(Customer $customer, int $customerId, int $createdAt, array $createdAtById = []): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO `' . self::ADDRESS_TABLE . '` ('
            . '`tenant_id`, `public_id`, `customer_id`, `address_type`, `label`, `addressee`, '
            . '`line1`, `line2`, `postal_code`, `city`, `region`, `country_code`, '
            . '`is_primary`, `position`, `created_at`, `updated_at`'
            . ') VALUES ('
            . ':tenant_id, :public_id, :customer_id, :address_type, :label, :addressee, '
            . ':line1, :line2, :postal_code, :city, :region, :country_code, '
            . ':is_primary, :position, :created_at, :updated_at'
            . ')'
        );
        foreach ($customer->getAddresses() as $address) {
            $statement->execute([
                ':tenant_id' => $customer->getTenantId()->toString(),
                ':public_id' => $address->getPublicId(),
                ':customer_id' => $customerId,
                ':address_type' => $address->getType()->getValue(),
                ':label' => $this->nullable($address->getLabel()),
                ':addressee' => $this->nullable($address->getAddressee()),
                ':line1' => $address->getLine1(),
                ':line2' => $this->nullable($address->getLine2()),
                ':postal_code' => $address->getPostalCode(),
                ':city' => $address->getCity(),
                ':region' => $this->nullable($address->getRegion()),
                ':country_code' => $address->getCountryCode(),
                ':is_primary' => $address->isPrimary() ? 1 : 0,
                ':position' => $address->getPosition(),
                ':created_at' => $createdAtById[$address->getPublicId()] ?? $createdAt,
                ':updated_at' => $createdAt,
            ]);
        }
    }

    private function findOne(string $where, array $parameters): ?Customer
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM `' . self::CUSTOMER_TABLE . '` WHERE ' . $where . ' LIMIT 1'
        );
        $statement->execute($parameters);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate($row);
    }

    private function hydrate(array $row): Customer
    {
        $tenantId = new TenantId((string)$row['tenant_id']);
        $customer = new Customer(
            (string)$row['public_id'],
            $tenantId,
            (string)$row['display_name'],
            (string)($row['company_name'] ?? ''),
            (string)($row['registration_number'] ?? ''),
            (string)($row['tax_number'] ?? ''),
            new CustomerStatus((string)$row['status']),
            (string)($row['notes'] ?? ''),
            (string)($row['source'] ?? ''),
            (string)($row['external_id'] ?? '')
        );

        $contactStatement = $this->connection->prepare(
            'SELECT * FROM `' . self::CONTACT_TABLE . '` '
            . 'WHERE `tenant_id` = :tenant_id AND `customer_id` = :customer_id ORDER BY `position`, `id`'
        );
        $contactStatement->execute([
            ':tenant_id' => $tenantId->toString(),
            ':customer_id' => (int)$row['id'],
        ]);
        foreach ($contactStatement->fetchAll(\PDO::FETCH_ASSOC) as $contact) {
            $customer->addContact(new Contact(
                (string)$contact['public_id'],
                (string)$contact['name'],
                (string)($contact['email'] ?? ''),
                (string)($contact['phone'] ?? ''),
                (string)($contact['role'] ?? ''),
                (bool)$contact['is_primary'],
                (int)$contact['position']
            ));
        }

        $addressStatement = $this->connection->prepare(
            'SELECT * FROM `' . self::ADDRESS_TABLE . '` '
            . 'WHERE `tenant_id` = :tenant_id AND `customer_id` = :customer_id '
            . 'ORDER BY `address_type`, `position`, `id`'
        );
        $addressStatement->execute([
            ':tenant_id' => $tenantId->toString(),
            ':customer_id' => (int)$row['id'],
        ]);
        foreach ($addressStatement->fetchAll(\PDO::FETCH_ASSOC) as $address) {
            $customer->addAddress(new Address(
                (string)$address['public_id'],
                new AddressType((string)$address['address_type']),
                (string)$address['line1'],
                (string)$address['postal_code'],
                (string)$address['city'],
                (string)$address['country_code'],
                (string)($address['label'] ?? ''),
                (string)($address['addressee'] ?? ''),
                (string)($address['line2'] ?? ''),
                (string)($address['region'] ?? ''),
                (bool)$address['is_primary'],
                (int)$address['position']
            ));
        }

        return $customer;
    }

    private function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function createdAtByPublicId(string $table, TenantId $tenantId, int $customerId): array
    {
        $statement = $this->connection->prepare(
            'SELECT `public_id`, `created_at` FROM `' . $table . '` '
            . 'WHERE `tenant_id` = :tenant_id AND `customer_id` = :customer_id'
        );
        $statement->execute([
            ':tenant_id' => $tenantId->toString(),
            ':customer_id' => $customerId,
        ]);
        $result = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[(string)$row['public_id']] = (int)$row['created_at'];
        }
        return $result;
    }

    private function assertTransaction(): void
    {
        if (!$this->connection->inTransaction()) {
            throw new \LogicException('Klantopslag vereist een actieve transactie.');
        }
    }

    private function isIntegrityViolation(\PDOException $exception): bool
    {
        return (string)$exception->getCode() === '23000'
            || (isset($exception->errorInfo[0]) && (string)$exception->errorInfo[0] === '23000');
    }
}
