<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Application\Query;

use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\CustomerStatus;

final class CustomerListQuery
{
    private const SORTS = ['display_name', 'company_name', 'primary_contact', 'city', 'created_at', 'status'];
    private const PAGE_SIZES = [10, 25, 50];

    /** @var string */ private $search;
    /** @var string */ private $status;
    /** @var string */ private $sort;
    /** @var string */ private $direction;
    /** @var int */ private $page;
    /** @var int */ private $perPage;

    public function __construct(
        string $search = '',
        string $status = '',
        string $sort = 'display_name',
        string $direction = 'asc',
        int $page = 1,
        int $perPage = 10
    ) {
        $search = trim($search);
        $status = strtolower(trim($status));
        $sort = strtolower(trim($sort));
        $direction = strtolower(trim($direction));
        if (strlen($search) > 120) {
            throw new \InvalidArgumentException('Zoekterm mag maximaal 120 tekens bevatten.');
        }
        if ($status !== '' && !in_array($status, CustomerStatus::values(), true)) {
            throw new \InvalidArgumentException('Ongeldig klantstatusfilter.');
        }
        if (!in_array($sort, self::SORTS, true)) {
            throw new \InvalidArgumentException('Ongeldige klantsorteerkolom.');
        }
        if (!in_array($direction, ['asc', 'desc'], true)) {
            throw new \InvalidArgumentException('Ongeldige sorteerrichting.');
        }
        if ($page < 1 || !in_array($perPage, self::PAGE_SIZES, true)) {
            throw new \InvalidArgumentException('Ongeldige paginering.');
        }

        $this->search = $search;
        $this->status = $status;
        $this->sort = $sort;
        $this->direction = $direction;
        $this->page = $page;
        $this->perPage = $perPage;
    }

    public function getSearch(): string { return $this->search; }
    public function getStatus(): string { return $this->status; }
    public function getSort(): string { return $this->sort; }
    public function getDirection(): string { return $this->direction; }
    public function getPage(): int { return $this->page; }
    public function getPerPage(): int { return $this->perPage; }
    /** @return string[] */ public static function sorts(): array { return self::SORTS; }
    /** @return int[] */ public static function pageSizes(): array { return self::PAGE_SIZES; }
}
