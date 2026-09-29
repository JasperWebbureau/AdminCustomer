<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Application\ReadModel;

final class CustomerOverviewSummary
{
    /** @var int */ private $total;
    /** @var int */ private $active;
    /** @var int */ private $inactive;

    public function __construct(int $total, int $active, int $inactive)
    {
        if ($total < 0 || $active < 0 || $inactive < 0 || $active + $inactive !== $total) {
            throw new \InvalidArgumentException('Ongeldige klantensamenvatting.');
        }
        $this->total = $total;
        $this->active = $active;
        $this->inactive = $inactive;
    }

    public function getTotal(): int { return $this->total; }
    public function getActive(): int { return $this->active; }
    public function getInactive(): int { return $this->inactive; }
}
