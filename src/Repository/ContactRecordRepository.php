<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Repository;

use Flexgrid\Modules\AdminCustomer\Entity\ContactRecord;
use Repository\Repository;

final class ContactRecordRepository extends Repository
{
    public function getEntity() { return new ContactRecord(); }
}
