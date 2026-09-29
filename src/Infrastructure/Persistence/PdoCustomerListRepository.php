<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Infrastructure\Persistence;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Application\Query\CustomerListQuery;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerListItem;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerListResult;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerOverviewSummary;
use Flexgrid\Modules\AdminCustomer\Contract\CustomerListRepositoryInterface;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\CustomerStatus;

final class PdoCustomerListRepository implements CustomerListRepositoryInterface
{
    private const CUSTOMER_TABLE = 'admin_customer';
    private const CONTACT_TABLE = 'admin_customer_contact';
    private const ADDRESS_TABLE = 'admin_customer_address';

    /** @var \PDO */ private $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function search(TenantId $tenantId, CustomerListQuery $query): CustomerListResult
    {
        list($where, $parameters) = $this->where($tenantId, $query);
        $count = $this->connection->prepare(
            'SELECT COUNT(*) FROM `' . self::CUSTOMER_TABLE . '` AS `customer` WHERE ' . $where
        );
        $count->execute($parameters);
        $total = (int)$count->fetchColumn();

        $sorts = [
            'display_name' => '`customer`.`display_name`',
            'company_name' => '`customer`.`company_name`',
            'primary_contact' => '`primary_contact_name`',
            'city' => '`billing_city`',
            'created_at' => '`customer`.`created_at`',
            'status' => '`customer`.`status`',
        ];
        $sort = $sorts[$query->getSort()];
        $direction = strtoupper($query->getDirection()) === 'DESC' ? 'DESC' : 'ASC';
        $offset = ($query->getPage() - 1) * $query->getPerPage();

        $statement = $this->connection->prepare(
            'SELECT `customer`.`public_id`, `customer`.`display_name`, `customer`.`company_name`, '
            . '`customer`.`status`, `customer`.`created_at`, '
            . $this->contactField('name', 'primary_contact_name') . ', '
            . $this->contactField('email', 'primary_contact_email') . ', '
            . $this->contactField('phone', 'primary_contact_phone') . ', '
            . $this->addressField('city', 'billing_city') . ', '
            . $this->addressField('country_code', 'billing_country_code') . ' '
            . 'FROM `' . self::CUSTOMER_TABLE . '` AS `customer` WHERE ' . $where . ' '
            . 'ORDER BY ' . $sort . ' ' . $direction . ', `customer`.`display_name` ASC, `customer`.`public_id` ASC '
            . 'LIMIT ' . (int)$query->getPerPage() . ' OFFSET ' . (int)$offset
        );
        $statement->execute($parameters);

        $items = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $items[] = new CustomerListItem(
                (string)$row['public_id'],
                (string)$row['display_name'],
                (string)($row['company_name'] ?? ''),
                (string)($row['primary_contact_name'] ?? ''),
                (string)($row['primary_contact_email'] ?? ''),
                (string)($row['primary_contact_phone'] ?? ''),
                (string)($row['billing_city'] ?? ''),
                (string)($row['billing_country_code'] ?? ''),
                (string)$row['status'],
                (int)$row['created_at']
            );
        }

        return new CustomerListResult($items, $total, $query->getPage(), $query->getPerPage());
    }

    public function getSummary(TenantId $tenantId): CustomerOverviewSummary
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS `total`, '
            . 'SUM(CASE WHEN `status` = :active THEN 1 ELSE 0 END) AS `active_count`, '
            . 'SUM(CASE WHEN `status` = :inactive THEN 1 ELSE 0 END) AS `inactive_count` '
            . 'FROM `' . self::CUSTOMER_TABLE . '` WHERE `tenant_id` = :tenant_id'
        );
        $statement->execute([
            ':tenant_id' => $tenantId->toString(),
            ':active' => CustomerStatus::ACTIVE,
            ':inactive' => CustomerStatus::INACTIVE,
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC) ?: [];

        return new CustomerOverviewSummary(
            (int)($row['total'] ?? 0),
            (int)($row['active_count'] ?? 0),
            (int)($row['inactive_count'] ?? 0)
        );
    }

    private function where(TenantId $tenantId, CustomerListQuery $query): array
    {
        $parts = ['`customer`.`tenant_id` = :tenant_id'];
        $parameters = [':tenant_id' => $tenantId->toString()];
        if ($query->getStatus() !== '') {
            $parts[] = '`customer`.`status` = :status';
            $parameters[':status'] = $query->getStatus();
        }
        if ($query->getSearch() !== '') {
            $search = '%' . $query->getSearch() . '%';
            $parts[] = '('
                . '`customer`.`display_name` LIKE :q1 OR `customer`.`company_name` LIKE :q2 '
                . 'OR `customer`.`registration_number` LIKE :q3 OR `customer`.`tax_number` LIKE :q4 '
                . 'OR EXISTS (SELECT 1 FROM `' . self::CONTACT_TABLE . '` AS `search_contact` '
                . 'WHERE `search_contact`.`tenant_id` = `customer`.`tenant_id` '
                . 'AND `search_contact`.`customer_id` = `customer`.`id` '
                . 'AND (`search_contact`.`name` LIKE :q5 OR `search_contact`.`email` LIKE :q6 OR `search_contact`.`phone` LIKE :q7)) '
                . 'OR EXISTS (SELECT 1 FROM `' . self::ADDRESS_TABLE . '` AS `search_address` '
                . 'WHERE `search_address`.`tenant_id` = `customer`.`tenant_id` '
                . 'AND `search_address`.`customer_id` = `customer`.`id` '
                . 'AND (`search_address`.`line1` LIKE :q8 OR `search_address`.`postal_code` LIKE :q9 OR `search_address`.`city` LIKE :q10))'
                . ')';
            foreach (range(1, 10) as $index) {
                $parameters[':q' . $index] = $search;
            }
        }

        return [implode(' AND ', $parts), $parameters];
    }

    private function contactField(string $field, string $alias): string
    {
        return 'COALESCE((SELECT `contact`.`' . $field . '` FROM `' . self::CONTACT_TABLE . '` AS `contact` '
            . 'WHERE `contact`.`tenant_id` = `customer`.`tenant_id` AND `contact`.`customer_id` = `customer`.`id` '
            . 'AND `contact`.`is_primary` = 1 ORDER BY `contact`.`position`, `contact`.`id` LIMIT 1), \'\') AS `' . $alias . '`';
    }

    private function addressField(string $field, string $alias): string
    {
        return 'COALESCE((SELECT `address`.`' . $field . '` FROM `' . self::ADDRESS_TABLE . '` AS `address` '
            . 'WHERE `address`.`tenant_id` = `customer`.`tenant_id` AND `address`.`customer_id` = `customer`.`id` '
            . 'AND `address`.`address_type` = \'billing\' AND `address`.`is_primary` = 1 '
            . 'ORDER BY `address`.`position`, `address`.`id` LIMIT 1), \'\') AS `' . $alias . '`';
    }
}
