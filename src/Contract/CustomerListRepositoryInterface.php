<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Contract;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Application\Query\CustomerListQuery;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerListResult;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerOverviewSummary;

interface CustomerListRepositoryInterface
{
    public function search(TenantId $tenantId, CustomerListQuery $query): CustomerListResult;
    public function getSummary(TenantId $tenantId): CustomerOverviewSummary;
}
