<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Contract;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;

interface CustomerRepositoryInterface
{
    public function insert(Customer $customer, int $createdAt): void;
    public function update(Customer $customer, int $updatedAt): void;
    public function findByPublicId(TenantId $tenantId, string $publicId): ?Customer;
    public function findByPublicIdForUpdate(TenantId $tenantId, string $publicId): ?Customer;
    public function findByExternalReference(TenantId $tenantId, string $source, string $externalId): ?Customer;
}
