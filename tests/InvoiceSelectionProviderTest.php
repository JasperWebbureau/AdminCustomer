<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$invoiceRoot = dirname(__DIR__, 2) . '/AdminInvoice/src';
require_once $invoiceRoot . '/Application/ReadModel/InvoiceCustomerSelection.php';
require_once $invoiceRoot . '/Contract/InvoiceCustomerProviderInterface.php';
require_once dirname(__DIR__) . '/src/Integration/Invoice/CustomerSelectionProvider.php';

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Address;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Contact;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\CustomerStatus;
use Flexgrid\Modules\AdminCustomer\Integration\Invoice\CustomerSelectionProvider;

$customer = new Customer(
    'customer-provider-1', new TenantId('provider-tenant'), 'Handelsnaam', 'Provider B.V.',
    '12345678', 'NL001234567B01'
);
$customer->addContact(new Contact(
    'contact-provider-1', 'Ada Admin', 'ada@example.test', '030-1234567', 'Administratie', true, 10
));
$customer->addAddress(new Address(
    'address-provider-1', new AddressType('billing'), 'Markt 1', '1000 AA', 'Utrecht', 'NL',
    'Facturatie', 'Provider B.V.', '', '', true, 10
));

$provider = new CustomerSelectionProvider();
$selectionMethod = new ReflectionMethod($provider, 'selection');
$selectionMethod->setAccessible(true);
$selection = $selectionMethod->invoke($provider, $customer);
adminCustomerAssert($selection->getPublicId() === 'customer-provider-1', 'Provider moet publieke Customer-id leveren.');
adminCustomerAssert($selection->getParty()['name'] === 'Provider B.V.', 'Provider moet bedrijfsnaam naar PartySnapshot kopiëren.');
adminCustomerAssert($selection->getParty()['contact_name'] === 'Ada Admin', 'Provider moet primair contact kopiëren.');
adminCustomerAssert($selection->getBillingAddress()['city'] === 'Utrecht', 'Provider moet primair factuuradres kopiëren.');

$inactive = new Customer(
    'customer-provider-inactive', new TenantId('provider-tenant'), 'Inactief', '', '', '',
    new CustomerStatus('inactive')
);
adminCustomerAssert($selectionMethod->invoke($provider, $inactive) === null, 'Inactieve klant mag niet selecteerbaar zijn.');

$withoutBilling = new Customer('customer-provider-no-address', new TenantId('provider-tenant'), 'Zonder adres');
adminCustomerAssert($selectionMethod->invoke($provider, $withoutBilling)->getBillingAddress()['line_1'] === '', 'Actieve klant zonder factuuradres moet selecteerbaar zijn.');

$sourceRoot = dirname(__DIR__) . '/src';
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceRoot)) as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }
    $normalized = str_replace('\\', '/', $file->getPathname());
    if (strpos($normalized, '/Integration/Invoice/') !== false) {
        continue;
    }
    adminCustomerAssert(
        strpos((string)file_get_contents($file->getPathname()), 'Flexgrid\\Modules\\AdminInvoice\\') === false,
        'Alleen AdminCustomer/Integration/Invoice mag AdminInvoice importeren.'
    );
}

echo "AdminCustomer Invoice selection provider tests passed.\n";
