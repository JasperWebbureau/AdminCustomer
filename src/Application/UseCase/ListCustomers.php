<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCustomer\Application\Query\CustomerListQuery;
use Flexgrid\Modules\AdminCustomer\Contract\CustomerListRepositoryInterface;

final class ListCustomers
{
    /** @var TenantContext */ private $tenantContext;
    /** @var CustomerListRepositoryInterface */ private $customers;

    public function __construct(TenantContext $tenantContext, CustomerListRepositoryInterface $customers)
    {
        $this->tenantContext = $tenantContext;
        $this->customers = $customers;
    }

    public function execute(CustomerListQuery $query): array
    {
        $tenantId = $this->tenantContext->getTenantId();
        return [
            'query' => $query,
            'result' => $this->customers->search($tenantId, $query),
            'summary' => $this->customers->getSummary($tenantId),
        ];
    }
}
