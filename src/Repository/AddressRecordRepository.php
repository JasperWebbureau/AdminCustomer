<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Repository;

use Flexgrid\Modules\AdminCustomer\Entity\AddressRecord;
use Repository\Repository;

final class AddressRecordRepository extends Repository
{
    public function getEntity() { return new AddressRecord(); }
}
