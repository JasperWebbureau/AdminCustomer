<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Contract;

use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerDetailContext;

interface CustomerDetailExtensionInterface
{
    public function render(CustomerDetailContext $context): string;
}
