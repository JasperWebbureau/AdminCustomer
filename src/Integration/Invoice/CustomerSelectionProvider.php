<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Integration\Invoice;

use Flexgrid\Modules\AdminCustomer\Application\Query\CustomerListQuery;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\CustomerStatus;
use Flexgrid\Modules\AdminCustomer\Exception\CustomerNotFoundException;
use Flexgrid\Modules\AdminCustomer\Service\AdminCustomerFactory;
use Flexgrid\Modules\AdminInvoice\Application\ReadModel\InvoiceCustomerSelection;
use Flexgrid\Modules\AdminInvoice\Contract\InvoiceCustomerProviderInterface;

final class CustomerSelectionProvider implements InvoiceCustomerProviderInterface
{
    public function search(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if ($query === '' || $limit < 1) {
            return [];
        }
        $result = AdminCustomerFactory::createListCustomers()->execute(new CustomerListQuery(
            substr($query, 0, 120),
            CustomerStatus::ACTIVE,
            'display_name',
            'asc',
            1,
            10
        ));
        $selections = [];
        foreach ($result['result']->getItems() as $item) {
            try {
                $selection = $this->selection(
                    AdminCustomerFactory::createGetCustomer()->execute($item->getPublicId())
                );
            } catch (CustomerNotFoundException $exception) {
                continue;
            }
            if ($selection !== null) {
                $selections[] = $selection;
            }
            if (count($selections) >= min(10, $limit)) {
                break;
            }
        }
        return $selections;
    }

    public function find(string $publicId): ?InvoiceCustomerSelection
    {
        try {
            return $this->selection(AdminCustomerFactory::createGetCustomer()->execute(trim($publicId)));
        } catch (CustomerNotFoundException $exception) {
            return null;
        }
    }

    private function selection(Customer $customer): ?InvoiceCustomerSelection
    {
        if ($customer->getStatus()->getValue() !== CustomerStatus::ACTIVE) {
            return null;
        }
        $address = $customer->getPrimaryAddress(new AddressType(AddressType::BILLING));
        if ($address === null) {
            return null;
        }
        $contact = $customer->getPrimaryContact();
        return new InvoiceCustomerSelection(
            $customer->getPublicId(),
            [
                'name' => $customer->getCompanyName() !== '' ? $customer->getCompanyName() : $customer->getDisplayName(),
                'contact_name' => $contact !== null ? $contact->getName() : '',
                'email' => $contact !== null ? $contact->getEmail() : '',
                'phone' => $contact !== null ? $contact->getPhone() : '',
                'registration_number' => $customer->getRegistrationNumber(),
                'tax_number' => $customer->getTaxNumber(),
            ],
            [
                'line_1' => $address->getLine1(),
                'line_2' => $address->getLine2(),
                'postal_code' => $address->getPostalCode(),
                'city' => $address->getCity(),
                'region' => $address->getRegion(),
                'country_code' => $address->getCountryCode(),
            ]
        );
    }
}
