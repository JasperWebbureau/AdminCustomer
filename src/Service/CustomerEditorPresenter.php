<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Service;

use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\CustomerStatus;

final class CustomerEditorPresenter
{
    public function present(Customer $customer, string $updateAction): array
    {
        $contacts = [];
        foreach ($customer->getContacts() as $contact) {
            $contacts[] = [
                'public_id' => $contact->getPublicId(),
                'name' => $contact->getName(),
                'email' => $contact->getEmail(),
                'phone' => $contact->getPhone(),
                'role' => $contact->getRole(),
                'is_primary' => $contact->isPrimary(),
            ];
        }
        $addresses = [];
        foreach ($customer->getAddresses() as $address) {
            $addresses[] = [
                'public_id' => $address->getPublicId(),
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
            ];
        }

        return [
            'publicId' => $customer->getPublicId(),
            'displayName' => $customer->getDisplayName(),
            'companyName' => $customer->getCompanyName(),
            'registrationNumber' => $customer->getRegistrationNumber(),
            'taxNumber' => $customer->getTaxNumber(),
            'status' => $customer->getStatus()->getValue(),
            'notes' => $customer->getNotes(),
            'contacts' => $contacts,
            'addresses' => $addresses,
            'updateAction' => $updateAction,
            'statusOptions' => [
                CustomerStatus::ACTIVE => 'Actief',
                CustomerStatus::INACTIVE => 'Inactief',
            ],
            'addressTypeOptions' => [
                AddressType::BILLING => 'Factuuradres',
                AddressType::SHIPPING => 'Verzendadres',
                AddressType::VISITING => 'Bezoekadres',
                AddressType::OTHER => 'Overig adres',
            ],
        ];
    }
}
