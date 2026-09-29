<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Application\ReadModel;

final class CustomerListResult
{
    /** @var CustomerListItem[] */ private $items;
    /** @var int */ private $total;
    /** @var int */ private $page;
    /** @var int */ private $perPage;

    public function __construct(array $items, int $total, int $page, int $perPage)
    {
        foreach ($items as $item) {
            if (!$item instanceof CustomerListItem) {
                throw new \InvalidArgumentException('Ongeldig klantenlijstitem.');
            }
        }
        if ($total < 0 || $page < 1 || $perPage < 1) {
            throw new \InvalidArgumentException('Ongeldige klantpaginering.');
        }
        $this->items = array_values($items);
        $this->total = $total;
        $this->page = $page;
        $this->perPage = $perPage;
    }

    /** @return CustomerListItem[] */ public function getItems(): array { return $this->items; }
    public function getTotal(): int { return $this->total; }
    public function getPage(): int { return $this->page; }
    public function getPerPage(): int { return $this->perPage; }
    public function getTotalPages(): int { return max(1, (int)ceil($this->total / $this->perPage)); }
    public function getFirstPosition(): int { return $this->total === 0 ? 0 : (($this->page - 1) * $this->perPage) + 1; }
    public function getLastPosition(): int { return min($this->total, $this->page * $this->perPage); }
}
