<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Address;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Contact;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType;

$customer = new Customer(
    'customer-domain-1',
    new TenantId('customer-domain-tenant'),
    'Acme administratie',
    'Acme B.V.',
    '12345678',
    'NL001234567B01'
);
adminCustomerAssertThrows(DomainException::class, function () use ($customer): void {
    $customer->addContact(new Contact('contact-invalid-first', 'Niet primair', '', '', '', false, 10));
}, 'Eerste contact moet primair zijn.');

$customer->addContact(new Contact('contact-1', 'Jasper Jansen', 'jasper@example.test', '+31 6 12345678', 'Inkoop', true, 10));
$customer->addContact(new Contact('contact-2', 'Tweede contact', 'twee@example.test', '', 'Administratie', false, 20));
adminCustomerAssert($customer->getPrimaryContact()->getPublicId() === 'contact-1', 'Primair contact moet expliciet vindbaar zijn.');
adminCustomerAssert(count($customer->getContacts()) === 2, 'Klant moet meerdere contacten ondersteunen.');
adminCustomerAssertThrows(DomainException::class, function () use ($customer): void {
    $customer->addContact(new Contact('contact-3', 'Nog primair', '', '010-123', '', true, 30));
}, 'Klant mag maar één primair contact hebben.');
adminCustomerAssertThrows(DomainException::class, function () use ($customer): void {
    $customer->addContact(new Contact('contact-4', 'Dubbele positie', '', '010-123', '', false, 20));
}, 'Contactposities moeten uniek zijn.');

$billing = new AddressType(AddressType::BILLING);
$customer->addAddress(new Address(
    'address-billing-1', $billing, 'Markt 1', '1000 AA', 'Utrecht', 'NL', 'Facturatie', 'Acme B.V.', '', '', true, 10
));
$customer->addAddress(new Address(
    'address-shipping-1', new AddressType(AddressType::SHIPPING), 'Haven 2', '3000 BB', 'Rotterdam', 'NL', '', '', '', '', true, 10
));
$customer->addAddress(new Address(
    'address-billing-2', $billing, 'Postbus 3', '1000 AC', 'Utrecht', 'NL', 'Alternatief', '', '', '', false, 20
));
adminCustomerAssert($customer->getPrimaryAddress($billing)->getPublicId() === 'address-billing-1', 'Primair factuuradres moet per type vindbaar zijn.');
adminCustomerAssert(count($customer->getAddresses()) === 3, 'Klant moet meerdere adressen en types ondersteunen.');
adminCustomerAssertThrows(DomainException::class, function () use ($customer, $billing): void {
    $customer->addAddress(new Address(
        'address-billing-3', $billing, 'Straat 4', '1000 AD', 'Utrecht', 'NL', '', '', '', '', true, 30
    ));
}, 'Per adrestype mag maar één primair adres bestaan.');

adminCustomerAssertThrows(InvalidArgumentException::class, function (): void {
    new Contact('contact-invalid-mail', 'Contact', 'geen-email');
}, 'Ongeldig e-mailadres moet worden geweigerd.');
adminCustomerAssertThrows(InvalidArgumentException::class, function (): void {
    new Address(
        'address-invalid-country', new AddressType(AddressType::BILLING), 'Straat 1', '1000 AA', 'Utrecht', 'Nederland'
    );
}, 'Adres moet een ISO-landcode met twee letters gebruiken.');
adminCustomerAssertThrows(InvalidArgumentException::class, function (): void {
    new Customer('customer-invalid-source', new TenantId('tenant'), 'Naam', '', '', '', null, '', 'webshop', '');
}, 'Bron en externe id moeten altijd samen worden opgegeven.');

echo "AdminCustomer domain tests passed.\n";
