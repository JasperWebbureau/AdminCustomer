<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCustomer\Contract\CustomerRepositoryInterface;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;
use Flexgrid\Modules\AdminCustomer\Exception\CustomerNotFoundException;

final class GetCustomer
{
    /** @var TenantContext */ private $tenantContext;
    /** @var CustomerRepositoryInterface */ private $customers;

    public function __construct(TenantContext $tenantContext, CustomerRepositoryInterface $customers)
    {
        $this->tenantContext = $tenantContext;
        $this->customers = $customers;
    }

    public function execute(string $publicId): Customer
    {
        $customer = $this->customers->findByPublicId(
            $this->tenantContext->getTenantId(),
            trim($publicId)
        );
        if ($customer === null) {
            throw new CustomerNotFoundException('Klant niet gevonden.');
        }
        return $customer;
    }
}
