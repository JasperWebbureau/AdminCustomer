<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/bootstrap.php';

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Infrastructure\PdoTransactionManager;
use Flexgrid\Modules\AdminCore\Infrastructure\UuidV4Generator;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Application\Command\CreateCustomerCommand;
use Flexgrid\Modules\AdminCustomer\Application\Query\CustomerListQuery;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\CreateCustomer;
use Flexgrid\Modules\AdminCustomer\Infrastructure\Persistence\PdoCustomerListRepository;
use Flexgrid\Modules\AdminCustomer\Infrastructure\Persistence\PdoCustomerRepository;
use Flexgrid\Utils\_Time;

function customerListCommand(
    string $name,
    string $registrationNumber,
    string $contactName,
    string $email,
    string $city,
    string $externalId
): CreateCustomerCommand {
    return new CreateCustomerCommand(
        $name,
        $name . ' B.V.',
        $registrationNumber,
        '',
        [['name' => $contactName, 'email' => $email]],
        [[
            'type' => 'billing',
            'line_1' => 'Teststraat 1',
            'postal_code' => '1234 AB',
            'city' => $city,
            'country_code' => 'NL',
        ]],
        '',
        'integration-test',
        $externalId
    );
}

$projectRoot = dirname(__DIR__, 4);
require $projectRoot . '/.env.php';
$connection = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['name'],
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
unset($db);

$tenantId = new TenantId('integration-admin-customer-list');
$otherTenantId = new TenantId('integration-admin-customer-list-other');
$transactions = new PdoTransactionManager($connection);
$repository = new PdoCustomerRepository($connection);
$clock = new _Time(strtotime('2026-09-18 12:00:00 UTC'));
$create = new CreateCustomer(new TenantContext($tenantId), new UuidV4Generator(), $transactions, $repository, $clock);
$createOther = new CreateCustomer(new TenantContext($otherTenantId), new UuidV4Generator(), $transactions, $repository, $clock);
$lists = new PdoCustomerListRepository($connection);

$connection->beginTransaction();
try {
    $alpha = $create->execute(customerListCommand(
        'Alpha klant', '11111111', 'Alice Administratie', 'alice@example.test', 'Utrecht', 'alpha'
    ));
    $create->execute(customerListCommand(
        'Beta klant', '22222222', 'Bob Beheer', 'bob@example.test', 'Rotterdam', 'beta'
    ));
    $gamma = $create->execute(customerListCommand(
        'Gamma klant', '33333333', 'Gina Geld', 'gina@example.test', 'Eindhoven', 'gamma'
    ));
    $createOther->execute(customerListCommand(
        'Andere tenant', '99999999', 'Otto Overig', 'otto@example.test', 'Utrecht', 'other'
    ));

    $deactivate = $connection->prepare(
        'UPDATE `admin_customer` SET `status` = :status WHERE `tenant_id` = :tenant_id AND `public_id` = :public_id'
    );
    $deactivate->execute([
        ':status' => 'inactive',
        ':tenant_id' => $tenantId->toString(),
        ':public_id' => $gamma->getPublicId(),
    ]);

    $all = $lists->search($tenantId, new CustomerListQuery('', '', 'display_name', 'asc'));
    adminCustomerAssert($all->getTotal() === 3, 'PDO-klantenlijst moet alleen klanten van de tenant tellen.');
    adminCustomerAssert($all->getItems()[0]->getPublicId() === $alpha->getPublicId(), 'PDO-klantenlijst moet whitelist-sortering toepassen.');
    adminCustomerAssert($all->getItems()[0]->getContactName() === 'Alice Administratie', 'Primair contact moet in de lijst worden gehydrateerd.');
    adminCustomerAssert($all->getItems()[0]->getCity() === 'Utrecht', 'Primair factuuradres moet in de lijst worden gehydrateerd.');

    foreach (['alice@example.test', 'Utrecht', '11111111'] as $searchTerm) {
        $search = $lists->search($tenantId, new CustomerListQuery($searchTerm));
        adminCustomerAssert($search->getTotal() === 1, 'Zoeken moet contact, adres en registratienummer ondersteunen.');
        adminCustomerAssert($search->getItems()[0]->getPublicId() === $alpha->getPublicId(), 'Zoeken mag geen andere klant teruggeven.');
    }

    $inactive = $lists->search($tenantId, new CustomerListQuery('', 'inactive'));
    adminCustomerAssert($inactive->getTotal() === 1, 'Statusfilter moet uitsluitend inactieve klanten tonen.');
    adminCustomerAssert($inactive->getItems()[0]->getPublicId() === $gamma->getPublicId(), 'Statusfilter moet de juiste klant teruggeven.');

    $summary = $lists->getSummary($tenantId);
    adminCustomerAssert($summary->getTotal() === 3, 'Samenvatting moet tenantgebonden totaal bevatten.');
    adminCustomerAssert($summary->getActive() === 2 && $summary->getInactive() === 1, 'Samenvatting moet statussen correct tellen.');

    $otherList = $lists->search($otherTenantId, new CustomerListQuery());
    adminCustomerAssert($otherList->getTotal() === 1, 'Andere tenant moet alleen de eigen klant zien.');
    adminCustomerAssert($otherList->getItems()[0]->getDisplayName() === 'Andere tenant', 'Tenantisolatie moet ook voor lijstitems gelden.');

    $connection->rollBack();
} catch (Throwable $throwable) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    throw $throwable;
}

foreach (['admin_customer_contact', 'admin_customer_address', 'admin_customer'] as $table) {
    foreach ([$tenantId, $otherTenantId] as $cleanupTenant) {
        $cleanup = $connection->prepare('SELECT COUNT(*) FROM `' . $table . '` WHERE `tenant_id` = :tenant_id');
        $cleanup->execute([':tenant_id' => $cleanupTenant->toString()]);
        adminCustomerAssert((int)$cleanup->fetchColumn() === 0, 'Lijstintegratietest liet fixtures achter in ' . $table . '.');
    }
}

echo "AdminCustomer PDO list repository tests passed.\n";
