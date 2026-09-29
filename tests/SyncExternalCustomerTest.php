<?php

declare(strict_types=1);

require_once __DIR__ . '/CreateCustomerTest.php';
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/src/Application/UseCase/SyncExternalCustomer.php';

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Application\Command\CreateCustomerCommand;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\CreateCustomer;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\SyncExternalCustomer;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\UpdateCustomer;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType;
use Flexgrid\Utils\_Time;

$tenant = new TenantContext(new TenantId('sync-tenant'));
$transactions = new CustomerTestTransactions();
$repository = new MemoryCustomers($transactions);
$ids = new CustomerTestIds();
$clock = new _Time(1770000000);
$sync = new SyncExternalCustomer(
    $tenant, $repository,
    new CreateCustomer($tenant, $ids, $transactions, $repository, $clock),
    new UpdateCustomer($tenant, $ids, $transactions, $repository, $clock)
);
$command = function (string $street): CreateCustomerCommand {
    return new CreateCustomerCommand(
        'Verhuurder BV', 'Verhuurder BV', '', '',
        [['name' => 'Contact', 'email' => 'contact@example.test', 'is_primary' => true]],
        [['type' => 'billing', 'line_1' => $street, 'postal_code' => '1000 AA',
            'city' => 'Amsterdam', 'country_code' => 'NL', 'is_primary' => true]],
        '', 'rental_agency', '42'
    );
};
$first = $sync->execute($command('Straat 1'));
$again = $sync->execute($command('Straat 1'));
adminCustomerAssert($first->getPublicId() === $again->getPublicId(), 'Een herhaalde synchronisatie mag geen tweede klant maken.');
$updated = $sync->execute($command('Straat 2'), 'inactive');
adminCustomerAssert($updated->getPublicId() === $first->getPublicId(), 'Een gewijzigde verhuurder houdt dezelfde klant-id.');
adminCustomerAssert($updated->getPrimaryAddress(new AddressType('billing'))->getPublicId()
    === $first->getPrimaryAddress(new AddressType('billing'))->getPublicId(),
    'Een gewijzigd factuuradres houdt zijn publieke child-id.');
adminCustomerAssert($updated->getPrimaryAddress(new AddressType('billing'))->getLine1() === 'Straat 2',
    'Gewijzigde brongegevens moeten op de klant worden bijgewerkt.');
adminCustomerAssert($updated->getStatus()->getValue() === 'inactive',
    'Inactieve bron moet niet als factureerbare klant zichtbaar zijn.');

echo "AdminCustomer external sync tests passed.\n";
