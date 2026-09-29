<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Repository;

use Flexgrid\Modules\AdminCustomer\Entity\CustomerRecord;
use Repository\Repository;

final class CustomerRecordRepository extends Repository
{
    public function getEntity() { return new CustomerRecord(); }
}
