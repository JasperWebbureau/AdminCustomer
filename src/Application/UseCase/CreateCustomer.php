<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\PublicIdGeneratorInterface;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminCustomer\Application\Command\CreateCustomerCommand;
use Flexgrid\Modules\AdminCustomer\Contract\CustomerRepositoryInterface;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Address;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Contact;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType;
use Flexgrid\Modules\AdminCustomer\Exception\DuplicateCustomerReferenceException;
use Flexgrid\Utils\_Time;

final class CreateCustomer
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

    public function execute(CreateCustomerCommand $command): Customer
    {
        $input = $this->normalize($command);
        $tenantId = $this->tenantContext->getTenantId();

        try {
            return $this->transactions->transactional(function () use ($input, $tenantId): Customer {
                if ($input['source'] !== '') {
                    $existing = $this->customers->findByExternalReference(
                        $tenantId,
                        $input['source'],
                        $input['external_id']
                    );
                    if ($existing !== null) {
                        $this->assertIdempotentMatch($existing, $input);
                        return $existing;
                    }
                }

                $customer = $this->buildCustomer($input);
                $this->customers->insert($customer, (int)$this->clock->get());

                return $customer;
            });
        } catch (DuplicateCustomerReferenceException $exception) {
            if ($input['source'] === '') {
                throw $exception;
            }
            $existing = $this->customers->findByExternalReference(
                $tenantId,
                $input['source'],
                $input['external_id']
            );
            if ($existing === null) {
                throw $exception;
            }
            $this->assertIdempotentMatch($existing, $input);
            return $existing;
        }
    }

    private function buildCustomer(array $input): Customer
    {
        $customer = new Customer(
            $this->publicIds->generate(),
            $this->tenantContext->getTenantId(),
            $input['display_name'],
            $input['company_name'],
            $input['registration_number'],
            $input['tax_number'],
            null,
            $input['notes'],
            $input['source'],
            $input['external_id']
        );
        foreach ($input['contacts'] as $contact) {
            $customer->addContact(new Contact(
                $this->publicIds->generate(),
                $contact['name'],
                $contact['email'],
                $contact['phone'],
                $contact['role'],
                $contact['is_primary'],
                $contact['position']
            ));
        }
        foreach ($input['addresses'] as $address) {
            $customer->addAddress(new Address(
                $this->publicIds->generate(),
                new AddressType($address['type']),
                $address['line_1'],
                $address['postal_code'],
                $address['city'],
                $address['country_code'],
                $address['label'],
                $address['addressee'],
                $address['line_2'],
                $address['region'],
                $address['is_primary'],
                $address['position']
            ));
        }

        return $customer;
    }

    private function normalize(CreateCustomerCommand $command): array
    {
        $contacts = [];
        foreach ($command->getContacts() as $index => $contact) {
            if (!is_array($contact)) {
                throw new \InvalidArgumentException('Ieder contact moet een array zijn.');
            }
            $contacts[] = [
                'name' => $this->text($contact, 'name', true),
                'email' => $this->text($contact, 'email'),
                'phone' => $this->text($contact, 'phone'),
                'role' => $this->text($contact, 'role'),
                'is_primary' => array_key_exists('is_primary', $contact)
                    ? $this->boolean($contact['is_primary'], 'is_primary')
                    : $index === 0,
                'position' => array_key_exists('position', $contact)
                    ? $this->position($contact['position'])
                    : ($index + 1) * 10,
            ];
        }
        usort($contacts, function (array $left, array $right): int {
            return $left['position'] <=> $right['position'];
        });

        $addresses = [];
        $typeCounts = [];
        foreach ($command->getAddresses() as $index => $address) {
            if (!is_array($address)) {
                throw new \InvalidArgumentException('Ieder adres moet een array zijn.');
            }
            $type = strtolower($this->text($address, 'type')) ?: AddressType::BILLING;
            new AddressType($type);
            $typeIndex = $typeCounts[$type] ?? 0;
            $typeCounts[$type] = $typeIndex + 1;
            $addresses[] = [
                'type' => $type,
                'label' => $this->text($address, 'label'),
                'addressee' => $this->text($address, 'addressee'),
                'line_1' => $this->text($address, 'line_1', true),
                'line_2' => $this->text($address, 'line_2'),
                'postal_code' => $this->text($address, 'postal_code', true),
                'city' => $this->text($address, 'city', true),
                'region' => $this->text($address, 'region'),
                'country_code' => strtoupper($this->text($address, 'country_code', true)),
                'is_primary' => array_key_exists('is_primary', $address)
                    ? $this->boolean($address['is_primary'], 'is_primary')
                    : $typeIndex === 0,
                'position' => array_key_exists('position', $address)
                    ? $this->position($address['position'])
                    : ($typeIndex + 1) * 10,
            ];
        }
        usort($addresses, function (array $left, array $right): int {
            $type = strcmp($left['type'], $right['type']);
            return $type !== 0 ? $type : ($left['position'] <=> $right['position']);
        });

        return [
            'display_name' => trim($command->getDisplayName()),
            'company_name' => trim($command->getCompanyName()),
            'registration_number' => trim($command->getRegistrationNumber()),
            'tax_number' => trim($command->getTaxNumber()),
            'contacts' => $contacts,
            'addresses' => $addresses,
            'notes' => trim($command->getNotes()),
            'source' => strtolower(trim($command->getSource())),
            'external_id' => trim($command->getExternalId()),
        ];
    }

    private function assertIdempotentMatch(Customer $customer, array $input): void
    {
        $actualContacts = [];
        foreach ($customer->getContacts() as $contact) {
            $actualContacts[] = [
                'name' => $contact->getName(),
                'email' => $contact->getEmail(),
                'phone' => $contact->getPhone(),
                'role' => $contact->getRole(),
                'is_primary' => $contact->isPrimary(),
                'position' => $contact->getPosition(),
            ];
        }
        $actualAddresses = [];
        foreach ($customer->getAddresses() as $address) {
            $actualAddresses[] = [
                'type' => $address->getType()->getValue(),
                'label' => $address->getLabel(),
                'addressee' => $address->getAddressee(),
                'line_1' => $address->getLine1(),
                'line_2' => $address->getLine2(),
                'postal_code' => $address->getPostalCode(),
                'city' => $address->getCity(),
                'region' => $address->getRegion(),
                'country_code' => $address->getCountryCode(),
                'is_primary' => $address->isPrimary(),
                'position' => $address->getPosition(),
            ];
        }

        if ($customer->getDisplayName() !== $input['display_name']
            || $customer->getCompanyName() !== $input['company_name']
            || $customer->getRegistrationNumber() !== $input['registration_number']
            || $customer->getTaxNumber() !== $input['tax_number']
            || $customer->getNotes() !== $input['notes']
            || $actualContacts !== $input['contacts']
            || $actualAddresses !== $input['addresses']
        ) {
            throw new DuplicateCustomerReferenceException(
                'Externe klantreferentie bestaat al met andere gegevens.'
            );
        }
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
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 1 || $value === '1') {
            return true;
        }
        if ($value === 0 || $value === '0') {
            return false;
        }
        throw new \InvalidArgumentException('Veld ' . $field . ' moet een boolean zijn.');
    }

    private function position($value): int
    {
        if (is_int($value) && $value >= 0) {
            return $value;
        }
        if (is_string($value) && preg_match('/^[0-9]+$/D', $value) === 1) {
            return (int)$value;
        }
        throw new \InvalidArgumentException('Positie moet een niet-negatief geheel getal zijn.');
    }
}
