<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$projectRoot = dirname(__DIR__, 4);
$frameworkSource = $projectRoot . '/flexgrid/flexgrid/src';

if (!class_exists('Repository\\RepositoryEntity')) {
    eval('namespace Repository; class RepositoryEntity {}');
}

require_once $frameworkSource . '/Utils/_Enum.php';
require_once $frameworkSource . '/Utils/_String.php';
require_once $frameworkSource . '/Database/Column/DefaultColumnLenth.php';
require_once $frameworkSource . '/Database/Column/ColumnType.php';
require_once $frameworkSource . '/Database/Column/Column.php';
require_once $frameworkSource . '/Autowire/Definition/PropertyDefinition.php';
require_once $frameworkSource . '/Autowire/Definition/EntityDefinition.php';
require_once $frameworkSource . '/Autowire/Scanner/EntityScanner.php';
require_once $frameworkSource . '/Autowire/Schema/SchemaIndexDefinition.php';
require_once dirname(__DIR__) . '/src/Entity/CustomerRecord.php';
require_once dirname(__DIR__) . '/src/Entity/ContactRecord.php';
require_once dirname(__DIR__) . '/src/Entity/AddressRecord.php';

use Flexgrid\Autowire\Scanner\EntityScanner;
use Flexgrid\Autowire\Schema\SchemaIndexDefinition;
use Flexgrid\Modules\AdminCustomer\Entity\AddressRecord;
use Flexgrid\Modules\AdminCustomer\Entity\ContactRecord;
use Flexgrid\Modules\AdminCustomer\Entity\CustomerRecord;

$scanner = new EntityScanner();
$entities = [
    'customer' => $scanner->parseEntity(CustomerRecord::class, dirname(__DIR__) . '/src/Entity/CustomerRecord.php'),
    'contact' => $scanner->parseEntity(ContactRecord::class, dirname(__DIR__) . '/src/Entity/ContactRecord.php'),
    'address' => $scanner->parseEntity(AddressRecord::class, dirname(__DIR__) . '/src/Entity/AddressRecord.php'),
];

adminCustomerAssert($entities['customer']->getTableName() === 'admin_customer', 'CustomerRecord moet zijn eigen moduletabel definiëren.');
adminCustomerAssert($entities['contact']->getTableName() === 'admin_customer_contact', 'ContactRecord moet een afzonderlijke childtabel definiëren.');
adminCustomerAssert($entities['address']->getTableName() === 'admin_customer_address', 'AddressRecord moet een afzonderlijke childtabel definiëren.');

$customerProperties = $entities['customer']->getProperties();
foreach (['tenantId', 'publicId', 'displayName', 'companyName', 'registrationNumber', 'taxNumber', 'status', 'source', 'externalId'] as $property) {
    adminCustomerAssert(isset($customerProperties[$property]), 'CustomerRecord mist property ' . $property . '.');
}
adminCustomerAssert($customerProperties['notes']->getDatabaseType() === 'TEXT', 'Klantnotities moeten TEXT-opslag gebruiken.');

$contactProperties = $entities['contact']->getProperties();
foreach (['tenantId', 'publicId', 'customerId', 'name', 'email', 'phone', 'role', 'isPrimary', 'position'] as $property) {
    adminCustomerAssert(isset($contactProperties[$property]), 'ContactRecord mist property ' . $property . '.');
}
adminCustomerAssert($contactProperties['customerId']->getDatabaseType() === 'INT', 'Contact-child-id moet bij Autowire primary INT aansluiten.');
adminCustomerAssert($contactProperties['isPrimary']->getDatabaseType() === 'TINYINT', 'Primary contactmarkering moet TINYINT zijn.');

$addressProperties = $entities['address']->getProperties();
foreach (['tenantId', 'publicId', 'customerId', 'addressType', 'line1', 'postalCode', 'city', 'countryCode', 'isPrimary', 'position'] as $property) {
    adminCustomerAssert(isset($addressProperties[$property]), 'AddressRecord mist property ' . $property . '.');
}
adminCustomerAssert($addressProperties['customerId']->getDatabaseType() === 'INT', 'Address-child-id moet bij Autowire primary INT aansluiten.');

$customerAnnotations = $entities['customer']->getClassAnnotations();
$externalIndex = SchemaIndexDefinition::fromEntityAnnotation(
    $entities['customer'],
    'tenant_source_external',
    $customerAnnotations['Index']['tenant_source_external']
);
adminCustomerAssert(strpos($externalIndex->buildCreateSql('admin_customer'), 'UNIQUE INDEX') !== false, 'Externe klantreferentie moet per tenant en bron uniek zijn.');

$contactAnnotations = $entities['contact']->getClassAnnotations();
$contactPosition = SchemaIndexDefinition::fromEntityAnnotation(
    $entities['contact'],
    'tenant_customer_position',
    $contactAnnotations['Index']['tenant_customer_position']
);
adminCustomerAssert(strpos($contactPosition->buildCreateSql('admin_customer_contact'), 'UNIQUE INDEX') !== false, 'Contactpositie moet per klant uniek zijn.');

$addressAnnotations = $entities['address']->getClassAnnotations();
$addressPosition = SchemaIndexDefinition::fromEntityAnnotation(
    $entities['address'],
    'tenant_customer_type_position',
    $addressAnnotations['Index']['tenant_customer_type_position']
);
adminCustomerAssert(strpos($addressPosition->buildCreateSql('admin_customer_address'), 'UNIQUE INDEX') !== false, 'Adrespositie moet per klant en type uniek zijn.');

$sourceRoot = dirname(__DIR__) . '/src';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceRoot));
$forbidden = [
    'Flexgrid\\Modules\\AdminPayment\\',
    'Flexgrid\\Modules\\Webshop\\',
    'App\\Administration\\',
];
foreach ($iterator as $sourceFile) {
    if (!$sourceFile->isFile() || strtolower($sourceFile->getExtension()) !== 'php') {
        continue;
    }
    $content = (string)file_get_contents($sourceFile->getPathname());
    $normalizedPath = str_replace('\\', '/', $sourceFile->getPathname());
    if (strpos($normalizedPath, '/Integration/Invoice/') === false) {
        adminCustomerAssert(
            strpos($content, 'Flexgrid\\Modules\\AdminInvoice\\') === false,
            'Alleen AdminCustomer/Integration/Invoice mag AdminInvoice importeren.'
        );
    }
    foreach ($forbidden as $namespace) {
        adminCustomerAssert(strpos($content, $namespace) === false, 'AdminCustomer bevat verboden module-import: ' . $namespace);
    }
}

echo "AdminCustomer persistence metadata tests passed.\n";
