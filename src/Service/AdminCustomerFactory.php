<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Service;

use Flexgrid\Database\Connection;
use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Infrastructure\PdoTransactionManager;
use Flexgrid\Modules\AdminCore\Infrastructure\UuidV4Generator;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\CreateCustomer;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\GetCustomer;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\ListCustomers;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\SyncExternalCustomer;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\UpdateCustomer;
use Flexgrid\Modules\AdminCustomer\Infrastructure\Persistence\PdoCustomerListRepository;
use Flexgrid\Modules\AdminCustomer\Infrastructure\Persistence\PdoCustomerRepository;
use Flexgrid\Utils\_Time;

final class AdminCustomerFactory
{
    public static function createListCustomers(): ListCustomers
    {
        return new ListCustomers(
            self::tenantContext(),
            new PdoCustomerListRepository(self::connection())
        );
    }

    public static function createOverviewPresenter(): CustomerOverviewPresenter
    {
        return new CustomerOverviewPresenter();
    }

    public static function createCreateCustomer(): CreateCustomer
    {
        $connection = self::connection();
        return new CreateCustomer(
            self::tenantContext(),
            new UuidV4Generator(),
            new PdoTransactionManager($connection),
            new PdoCustomerRepository($connection),
            new _Time()
        );
    }

    public static function createGetCustomer(): GetCustomer
    {
        return new GetCustomer(
            self::tenantContext(),
            new PdoCustomerRepository(self::connection())
        );
    }

    public static function createSyncExternalCustomer(): SyncExternalCustomer
    {
        $connection = self::connection();
        $tenant = self::tenantContext();
        $repository = new PdoCustomerRepository($connection);
        $transactions = new PdoTransactionManager($connection);
        $ids = new UuidV4Generator();
        $clock = new _Time();
        return new SyncExternalCustomer(
            $tenant,
            $repository,
            new CreateCustomer($tenant, $ids, $transactions, $repository, $clock),
            new UpdateCustomer($tenant, $ids, $transactions, $repository, $clock)
        );
    }

    public static function createUpdateCustomer(): UpdateCustomer
    {
        $connection = self::connection();
        return new UpdateCustomer(
            self::tenantContext(),
            new UuidV4Generator(),
            new PdoTransactionManager($connection),
            new PdoCustomerRepository($connection),
            new _Time()
        );
    }

    public static function createEditorPresenter(): CustomerEditorPresenter
    {
        return new CustomerEditorPresenter();
    }

    private static function connection(): \PDO
    {
        return Connection::getConnections();
    }

    private static function tenantContext(): TenantContext
    {
        if (!defined('__ADMIN_TENANT_ID__')) {
            throw new \LogicException('Definieer __ADMIN_TENANT_ID__ expliciet voor de Admin-modules.');
        }
        return new TenantContext(new TenantId((string)constant('__ADMIN_TENANT_ID__')));
    }
}
