<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/flexgrid/src/Database/Connection.php';
require_once dirname(__DIR__) . '/src/Service/AdminCustomerFactory.php';

$invoiceRoot = dirname(__DIR__, 2) . '/AdminInvoice/src';
require_once $invoiceRoot . '/Application/ReadModel/InvoiceCustomerSelection.php';
require_once $invoiceRoot . '/Contract/InvoiceCustomerProviderInterface.php';
require_once dirname(__DIR__) . '/src/Integration/Invoice/CustomerSelectionProvider.php';

use Flexgrid\Database\Connection;
use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Infrastructure\PdoTransactionManager;
use Flexgrid\Modules\AdminCore\Infrastructure\UuidV4Generator;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Application\Command\CreateCustomerCommand;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\CreateCustomer;
use Flexgrid\Modules\AdminCustomer\Infrastructure\Persistence\PdoCustomerRepository;
use Flexgrid\Modules\AdminCustomer\Integration\Invoice\CustomerSelectionProvider;
use Flexgrid\Utils\_Time;

$projectRoot = dirname(__DIR__, 4);
require $projectRoot . '/.env.php';
$connection = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['name'],
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$databaseName = $db['name'];
unset($db);
Connection::$connections[$databaseName] = $connection;
Connection::$default = $databaseName;

$tenantId = new TenantId((string)constant('__ADMIN_TENANT_ID__'));
$create = new CreateCustomer(
    new TenantContext($tenantId),
    new UuidV4Generator(),
    new PdoTransactionManager($connection),
    new PdoCustomerRepository($connection),
    new _Time(strtotime('2026-09-18 14:00:00 UTC'))
);
$provider = new CustomerSelectionProvider();
$uniqueName = 'Invoice selector integratie 7f92a1';

$connection->beginTransaction();
try {
    $customer = $create->execute(new CreateCustomerCommand(
        $uniqueName,
        $uniqueName . ' B.V.',
        '76543210',
        'NL007654321B01',
        [['name' => 'Selectie Contact', 'email' => 'selector@example.test', 'phone' => '030-7654321']],
        [[
            'type' => 'billing', 'line_1' => 'Selectiestraat 7', 'postal_code' => '7000 ZZ',
            'city' => 'Utrecht', 'country_code' => 'NL',
        ]]
    ));

    $results = $provider->search('7f92a1', 10);
    adminCustomerAssert(count($results) === 1, 'Echte providerzoekopdracht moet de tenantklant vinden.');
    adminCustomerAssert($results[0]->getPublicId() === $customer->getPublicId(), 'Provider moet publieke klant-id teruggeven.');
    adminCustomerAssert($results[0]->getParty()['email'] === 'selector@example.test', 'Provider moet primair contact naar snapshotdata kopiëren.');
    adminCustomerAssert($results[0]->getBillingAddress()['line_1'] === 'Selectiestraat 7', 'Provider moet primair factuuradres kopiëren.');

    $found = $provider->find($customer->getPublicId());
    adminCustomerAssert($found !== null && $found->getParty()['name'] === $uniqueName . ' B.V.', 'Provider-find moet dezelfde tenantgebonden selectie leveren.');

    $connection->rollBack();
} catch (Throwable $throwable) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    throw $throwable;
}

foreach (['admin_customer_contact', 'admin_customer_address', 'admin_customer'] as $table) {
    $cleanup = $connection->prepare('SELECT COUNT(*) FROM `' . $table . '` WHERE `tenant_id` = :tenant_id AND '
        . ($table === 'admin_customer' ? '`display_name` = :marker' : '`created_at` = :marker'));
    $cleanup->execute([
        ':tenant_id' => $tenantId->toString(),
        ':marker' => $table === 'admin_customer' ? $uniqueName : strtotime('2026-09-18 14:00:00 UTC'),
    ]);
    adminCustomerAssert((int)$cleanup->fetchColumn() === 0, 'Providerintegratietest liet fixtures achter in ' . $table . '.');
}

echo "AdminCustomer PDO Invoice selection provider tests passed.\n";
