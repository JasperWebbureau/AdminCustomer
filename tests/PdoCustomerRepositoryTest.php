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
use Flexgrid\Modules\AdminCustomer\Application\Command\UpdateCustomerCommand;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\CreateCustomer;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\UpdateCustomer;
use Flexgrid\Modules\AdminCustomer\Exception\DuplicateCustomerReferenceException;
use Flexgrid\Modules\AdminCustomer\Infrastructure\Persistence\PdoCustomerRepository;
use Flexgrid\Utils\_Time;

$projectRoot = dirname(__DIR__, 4);
require $projectRoot . '/.env.php';
$connection = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['name'],
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
unset($db);

$tenantId = new TenantId('integration-admin-customer');
$repository = new PdoCustomerRepository($connection);
$useCase = new CreateCustomer(
    new TenantContext($tenantId),
    new UuidV4Generator(),
    new PdoTransactionManager($connection),
    $repository,
    new _Time(strtotime('2026-09-18 12:00:00 UTC'))
);
$command = new CreateCustomerCommand(
    'PDO klant',
    'PDO Klant B.V.',
    '87654321',
    'NL009876543B01',
    [
        ['name' => 'Primair contact', 'email' => 'primary@example.test', 'role' => 'Administratie'],
        ['name' => 'Tweede contact', 'phone' => '010-1234567'],
    ],
    [
        ['type' => 'billing', 'line_1' => 'Teststraat 1', 'postal_code' => '1234 AB', 'city' => 'Utrecht', 'country_code' => 'NL'],
        ['type' => 'billing', 'line_1' => 'Postbus 2', 'postal_code' => '1234 AC', 'city' => 'Utrecht', 'country_code' => 'NL'],
        ['type' => 'shipping', 'line_1' => 'Magazijn 3', 'postal_code' => '5678 CD', 'city' => 'Rotterdam', 'country_code' => 'NL'],
    ],
    'PDO notitie',
    'webshop',
    'pdo-customer-1'
);

$connection->beginTransaction();
try {
    $created = $useCase->execute($command);
    $loaded = $repository->findByPublicId($tenantId, $created->getPublicId());
    adminCustomerAssert($loaded !== null && $loaded->getDisplayName() === 'PDO klant', 'PDO-repository moet klant tenantgebonden hydrateren.');
    adminCustomerAssert(count($loaded->getContacts()) === 2, 'PDO-repository moet alle contacten hydrateren.');
    adminCustomerAssert($loaded->getPrimaryContact()->getEmail() === 'primary@example.test', 'Primair contact moet behouden blijven.');
    adminCustomerAssert(count($loaded->getAddresses()) === 3, 'PDO-repository moet alle adressen hydrateren.');
    adminCustomerAssert($loaded->getPrimaryAddress(new \Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType('billing'))->getLine1() === 'Teststraat 1', 'Primair factuuradres moet behouden blijven.');
    adminCustomerAssert($repository->findByPublicId(new TenantId('other-customer-tenant'), $created->getPublicId()) === null, 'Andere tenant mag klant niet lezen.');

    $retry = $useCase->execute($command);
    adminCustomerAssert($retry->getPublicId() === $created->getPublicId(), 'PDO-retry moet idempotent dezelfde klant teruggeven.');
    $count = $connection->prepare('SELECT COUNT(*) FROM `admin_customer` WHERE `tenant_id` = :tenant_id');
    $count->execute([':tenant_id' => $tenantId->toString()]);
    adminCustomerAssert((int)$count->fetchColumn() === 1, 'Idempotente retry mag geen tweede klant maken.');

    adminCustomerAssertThrows(DuplicateCustomerReferenceException::class, function () use ($useCase): void {
        $useCase->execute(new CreateCustomerCommand(
            'Afwijkende PDO klant', '', '', '', [], [], '', 'webshop', 'pdo-customer-1'
        ));
    }, 'Afwijkende PDO-retry moet als conflict worden geweigerd.');

    $manualOne = $useCase->execute(new CreateCustomerCommand('PDO handmatig één'));
    $manualTwo = $useCase->execute(new CreateCustomerCommand('PDO handmatig twee'));
    adminCustomerAssert($manualOne->getPublicId() !== $manualTwo->getPublicId(), 'Meerdere handmatige klanten met SQL NULL-referentie moeten mogelijk zijn.');

    $updated = (new UpdateCustomer(
        new TenantContext($tenantId),
        new UuidV4Generator(),
        new PdoTransactionManager($connection),
        $repository,
        new _Time(strtotime('2026-09-18 13:00:00 UTC'))
    ))->execute(new UpdateCustomerCommand(
        $created->getPublicId(),
        'PDO klant gewijzigd',
        'PDO Klant Nieuw B.V.',
        '87654321',
        'NL009876543B01',
        'inactive',
        'Gewijzigde PDO notitie',
        [
            [
                'public_id' => $created->getContacts()[0]->getPublicId(),
                'name' => 'Primair gewijzigd',
                'email' => 'changed@example.test',
                'is_primary' => '1',
            ],
            [
                'public_id' => '',
                'name' => 'Nieuw contact',
                'phone' => '06-12345678',
                'is_primary' => '0',
            ],
        ],
        [
            [
                'public_id' => $created->getAddresses()[0]->getPublicId(),
                'type' => 'billing',
                'line_1' => 'Nieuwe straat 10',
                'postal_code' => '4321 BA',
                'city' => 'Eindhoven',
                'country_code' => 'NL',
                'is_primary' => '1',
            ],
            [
                'public_id' => $created->getPrimaryAddress(new \Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType('shipping'))->getPublicId(),
                'type' => 'shipping',
                'line_1' => 'Nieuw magazijn 4',
                'postal_code' => '5678 CD',
                'city' => 'Rotterdam',
                'country_code' => 'NL',
                'is_primary' => '1',
            ],
        ]
    ));
    adminCustomerAssert($updated->getStatus()->getValue() === 'inactive', 'PDO-update moet klantstatus wijzigen.');
    $reloaded = $repository->findByPublicId($tenantId, $created->getPublicId());
    adminCustomerAssert($reloaded !== null && $reloaded->getDisplayName() === 'PDO klant gewijzigd', 'PDO-update moet algemene gegevens bewaren.');
    adminCustomerAssert(count($reloaded->getContacts()) === 2, 'PDO-update moet childcollectie atomair vervangen.');
    adminCustomerAssert($reloaded->getPrimaryContact()->getEmail() === 'changed@example.test', 'PDO-update moet primair contact bewaren.');
    adminCustomerAssert(count($reloaded->getAddresses()) === 2, 'PDO-update moet verwijderde adressen niet behouden.');
    adminCustomerAssert($reloaded->getSource() === 'webshop' && $reloaded->getExternalId() === 'pdo-customer-1', 'PDO-update moet externe bronreferentie immutable houden.');

    $connection->rollBack();
} catch (Throwable $throwable) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    throw $throwable;
}

foreach (['admin_customer_contact', 'admin_customer_address', 'admin_customer'] as $table) {
    $cleanup = $connection->prepare('SELECT COUNT(*) FROM `' . $table . '` WHERE `tenant_id` = :tenant_id');
    $cleanup->execute([':tenant_id' => $tenantId->toString()]);
    adminCustomerAssert((int)$cleanup->fetchColumn() === 0, 'Integratietest liet fixtures achter in ' . $table . '.');
}

echo "AdminCustomer PDO repository tests passed.\n";
