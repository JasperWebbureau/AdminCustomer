<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\PublicIdGeneratorInterface;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminCustomer\Application\Command\UpdateCustomerCommand;
use Flexgrid\Modules\AdminCustomer\Contract\CustomerRepositoryInterface;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Address;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Contact;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\CustomerStatus;
use Flexgrid\Modules\AdminCustomer\Exception\CustomerNotFoundException;
use Flexgrid\Utils\_Time;

final class UpdateCustomer
{
    /** @var TenantContext */ private $tenantContext;
    /** @var PublicIdGeneratorInterface */ private $publicIds;
    /** @var TransactionManagerInterface */ private $transactions;
    /** @var CustomerRepositoryInterface */ private $customers;
    /** @var _Time */ private $clock;

    public function __construct(
        TenantContext $tenantContext,
        PublicIdGeneratorInterface $publicIds,
        TransactionManagerInterface $transactions,
        CustomerRepositoryInterface $customers,
        _Time $clock
    ) {
        $this->tenantContext = $tenantContext;
        $this->publicIds = $publicIds;
        $this->transactions = $transactions;
        $this->customers = $customers;
        $this->clock = $clock;
    }

    public function execute(UpdateCustomerCommand $command): Customer
    {
        if (count($command->getContacts()) > 100 || count($command->getAddresses()) > 100) {
            throw new \InvalidArgumentException('Een klant kan maximaal 100 contacten en 100 adressen bevatten.');
        }

        return $this->transactions->transactional(function () use ($command): Customer {
            $tenantId = $this->tenantContext->getTenantId();
            $existing = $this->customers->findByPublicIdForUpdate($tenantId, $command->getPublicId());
            if ($existing === null) {
                throw new CustomerNotFoundException('Klant niet gevonden.');
            }

            $customer = new Customer(
                $existing->getPublicId(),
                $tenantId,
                $command->getDisplayName(),
                $command->getCompanyName(),
                $command->getRegistrationNumber(),
                $command->getTaxNumber(),
                new CustomerStatus($command->getStatus()),
                $command->getNotes(),
                $existing->getSource(),
                $existing->getExternalId()
            );
            $this->addContacts($customer, $existing, $command->getContacts());
            $this->addAddresses($customer, $existing, $command->getAddresses());
            $this->customers->update($customer, (int)$this->clock->get());

            return $customer;
        });
    }

    private function addContacts(Customer $customer, Customer $existing, array $rows): void
    {
        $allowed = [];
        foreach ($existing->getContacts() as $contact) {
            $allowed[$contact->getPublicId()] = true;
        }
        $used = [];
        $contacts = [];
        foreach (array_values($rows) as $index => $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException('Ieder contact moet geldige velden bevatten.');
            }
            $publicId = $this->childId($row, $allowed, $used, 'contact');
            $contacts[] = [
                'primary' => $this->boolean($row['is_primary'] ?? '0', 'is_primary'),
                'position' => ($index + 1) * 10,
                'contact' => new Contact(
                    $publicId,
                    $this->text($row, 'name', true),
                    $this->text($row, 'email'),
                    $this->text($row, 'phone'),
                    $this->text($row, 'role'),
                    $this->boolean($row['is_primary'] ?? '0', 'is_primary'),
                    ($index + 1) * 10
                ),
            ];
        }
        usort($contacts, function (array $left, array $right): int {
            $primary = (int)$right['primary'] <=> (int)$left['primary'];
            return $primary !== 0 ? $primary : ($left['position'] <=> $right['position']);
        });
        foreach ($contacts as $entry) {
            $customer->addContact($entry['contact']);
        }
    }

    private function addAddresses(Customer $customer, Customer $existing, array $rows): void
    {
        $allowed = [];
        foreach ($existing->getAddresses() as $address) {
            $allowed[$address->getPublicId()] = true;
        }
        $used = [];
        $typePositions = [];
        $addresses = [];
        foreach (array_values($rows) as $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException('Ieder adres moet geldige velden bevatten.');
            }
            $publicId = $this->childId($row, $allowed, $used, 'adres');
            $type = strtolower($this->text($row, 'type', true));
            $typePositions[$type] = ($typePositions[$type] ?? 0) + 1;
            $primary = $this->boolean($row['is_primary'] ?? '0', 'is_primary');
            $addresses[] = [
                'type' => $type,
                'primary' => $primary,
                'position' => $typePositions[$type] * 10,
                'address' => new Address(
                    $publicId,
                    new AddressType($type),
                    $this->text($row, 'line_1', true),
                    $this->text($row, 'postal_code', true),
                    $this->text($row, 'city', true),
                    strtoupper($this->text($row, 'country_code', true)),
                    $this->text($row, 'label'),
                    $this->text($row, 'addressee'),
                    $this->text($row, 'line_2'),
                    $this->text($row, 'region'),
                    $primary,
                    $typePositions[$type] * 10
                ),
            ];
        }
        usort($addresses, function (array $left, array $right): int {
            $type = strcmp($left['type'], $right['type']);
            if ($type !== 0) {
                return $type;
            }
            $primary = (int)$right['primary'] <=> (int)$left['primary'];
            return $primary !== 0 ? $primary : ($left['position'] <=> $right['position']);
        });
        foreach ($addresses as $entry) {
            $customer->addAddress($entry['address']);
        }
    }

    private function childId(array $row, array $allowed, array &$used, string $type): string
    {
        $publicId = $this->text($row, 'public_id');
        if ($publicId === '') {
            $publicId = $this->publicIds->generate();
        } elseif (!isset($allowed[$publicId])) {
            throw new \InvalidArgumentException('Onbekende ' . $type . '-id.');
        }
        if (isset($used[$publicId])) {
            throw new \InvalidArgumentException('Dubbele ' . $type . '-id.');
        }
        $used[$publicId] = true;
        return $publicId;
    }

    private function text(array $data, string $key, bool $required = false): string
    {
        $value = $data[$key] ?? '';
        if (!is_string($value) && !is_int($value)) {
            throw new \InvalidArgumentException('Veld ' . $key . ' moet tekst bevatten.');
        }
        $value = trim((string)$value);
        if ($required && $value === '') {
            throw new \InvalidArgumentException('Veld ' . $key . ' is verplicht.');
        }
        return $value;
    }

    private function boolean($value, string $field): bool
    {
        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }
        if ($value === false || $value === 0 || $value === '0') {
            return false;
        }
        throw new \InvalidArgumentException('Veld ' . $field . ' moet een boolean zijn.');
    }
}
