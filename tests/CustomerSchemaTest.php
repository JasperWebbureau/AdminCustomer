<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/bootstrap.php';

$projectRoot = dirname(__DIR__, 4);
require $projectRoot . '/.env.php';
$connection = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['name'],
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
unset($db);

function adminCustomerSchemaColumns(PDO $connection, string $table): array
{
    $columns = [];
    foreach ($connection->query('SHOW FULL COLUMNS FROM `' . $table . '`')->fetchAll() as $row) {
        $columns[$row['Field']] = $row;
    }
    return $columns;
}

function adminCustomerSchemaIndex(PDO $connection, string $table, string $index): array
{
    $statement = $connection->prepare(
        'SELECT `NON_UNIQUE` AS `Non_unique`, `SEQ_IN_INDEX` AS `Seq_in_index`, `COLUMN_NAME` AS `Column_name` '
        . 'FROM information_schema.statistics '
        . 'WHERE table_schema = DATABASE() AND table_name = :table AND index_name = :index ORDER BY `Seq_in_index`'
    );
    $statement->execute([':table' => $table, ':index' => $index]);
    return $statement->fetchAll();
}

$customer = adminCustomerSchemaColumns($connection, 'admin_customer');
foreach ([
    'tenant_id' => 'varchar(64)', 'public_id' => 'varchar(64)', 'display_name' => 'varchar(255)',
    'company_name' => 'varchar(255)', 'registration_number' => 'varchar(64)', 'tax_number' => 'varchar(64)',
    'status' => 'varchar(16)', 'notes' => 'text', 'source' => 'varchar(64)', 'external_id' => 'varchar(128)',
] as $column => $type) {
    adminCustomerAssert(isset($customer[$column]), 'Databasekolom admin_customer.' . $column . ' ontbreekt.');
    adminCustomerAssert(strpos(strtolower((string)$customer[$column]['Type']), $type) === 0, 'Onverwacht type voor admin_customer.' . $column . '.');
}
foreach (['tenant_id', 'public_id', 'display_name', 'status', 'created_at', 'updated_at'] as $column) {
    adminCustomerAssert($customer[$column]['Null'] === 'NO', 'Databasekolom admin_customer.' . $column . ' moet NOT NULL zijn.');
}
foreach (['source', 'external_id'] as $column) {
    adminCustomerAssert($customer[$column]['Null'] === 'YES', 'Optionele klantreferentie moet SQL NULL toestaan.');
}

$contact = adminCustomerSchemaColumns($connection, 'admin_customer_contact');
foreach ([
    'tenant_id' => 'varchar(64)', 'public_id' => 'varchar(64)', 'customer_id' => 'int',
    'name' => 'varchar(255)', 'email' => 'varchar(255)', 'phone' => 'varchar(64)',
    'role' => 'varchar(128)', 'is_primary' => 'tinyint', 'position' => 'int',
] as $column => $type) {
    adminCustomerAssert(isset($contact[$column]), 'Databasekolom admin_customer_contact.' . $column . ' ontbreekt.');
    adminCustomerAssert(strpos(strtolower((string)$contact[$column]['Type']), $type) === 0, 'Onverwacht type voor admin_customer_contact.' . $column . '.');
}

$address = adminCustomerSchemaColumns($connection, 'admin_customer_address');
foreach ([
    'tenant_id' => 'varchar(64)', 'public_id' => 'varchar(64)', 'customer_id' => 'int',
    'address_type' => 'varchar(16)', 'line1' => 'varchar(255)', 'postal_code' => 'varchar(32)',
    'city' => 'varchar(128)', 'country_code' => 'varchar(2)', 'is_primary' => 'tinyint', 'position' => 'int',
] as $column => $type) {
    adminCustomerAssert(isset($address[$column]), 'Databasekolom admin_customer_address.' . $column . ' ontbreekt.');
    adminCustomerAssert(strpos(strtolower((string)$address[$column]['Type']), $type) === 0, 'Onverwacht type voor admin_customer_address.' . $column . '.');
}

foreach ([
    ['admin_customer', 'tenant_public', ['tenant_id', 'public_id'], true],
    ['admin_customer', 'tenant_source_external', ['tenant_id', 'source', 'external_id'], true],
    ['admin_customer_contact', 'tenant_public', ['tenant_id', 'public_id'], true],
    ['admin_customer_contact', 'tenant_customer_position', ['tenant_id', 'customer_id', 'position'], true],
    ['admin_customer_address', 'tenant_public', ['tenant_id', 'public_id'], true],
    ['admin_customer_address', 'tenant_customer_type_position', ['tenant_id', 'customer_id', 'address_type', 'position'], true],
] as $expected) {
    list($table, $name, $columns, $unique) = $expected;
    $rows = adminCustomerSchemaIndex($connection, $table, $name);
    adminCustomerAssert(array_column($rows, 'Column_name') === $columns, 'Index ' . $table . '.' . $name . ' heeft een onverwachte kolomvolgorde.');
    foreach ($rows as $row) {
        adminCustomerAssert((int)$row['Non_unique'] === ($unique ? 0 : 1), 'Index ' . $table . '.' . $name . ' heeft onverwachte uniqueness.');
    }
}

echo "AdminCustomer database schema tests passed.\n";
