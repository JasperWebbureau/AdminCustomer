<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Address;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Contact;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType;
use Flexgrid\Modules\AdminCustomer\Service\CustomerEditorPresenter;

$customer = new Customer('customer-template', new TenantId('template-tenant'), '<script>alert(1)</script>', 'Veilig B.V.');
$customer->addContact(new Contact('contact-template', 'Ada', 'ada@example.test', '', '', true, 10));
$customer->addAddress(new Address(
    'address-template', new AddressType('billing'), 'Straat 1', '1000 AA', 'Utrecht', 'NL', '', '', '', '', true, 10
));
$viewModel = (new CustomerEditorPresenter())->present($customer, 'customer-update-event');

extract($viewModel, EXTR_OVERWRITE);
ob_start();
include dirname(__DIR__) . '/src/Templates/Editor/Content.php';
$html = (string)ob_get_clean();

adminCustomerAssert(strpos($html, 'data-admin-customer-form') !== false, 'Editor moet een stabiele AJAX-formuliercontainer hebben.');
adminCustomerAssert(strpos($html, 'ajax="true"') !== false, 'Editor moet Flexgrids declaratieve AJAX-afhandeling gebruiken.');
adminCustomerAssert(strpos($html, 'action="customer-update-event"') !== false, 'Editor moet de update-eventnaam gebruiken.');
adminCustomerAssert(strpos($html, '<script>alert(1)</script>') === false, 'Klantgegevens mogen geen HTML injecteren.');
adminCustomerAssert(strpos($html, '&lt;script&gt;alert(1)&lt;/script&gt;') !== false, 'Klantnaam moet zichtbaar maar escaped blijven.');
adminCustomerAssert(strpos($html, 'contacts[contact_0][public_id]') !== false, 'Bestaande contact-id moet als aggregate-child worden meegestuurd.');
adminCustomerAssert(strpos($html, 'addresses[address_0][type]') !== false, 'Editor moet getypeerde adressen renderen.');
adminCustomerAssert(strpos($html, 'data-admin-customer-template="contact"') !== false, 'Editor moet client-side nieuwe contacten kunnen toevoegen.');
adminCustomerAssert(strpos($html, 'data-admin-customer-template="address"') !== false, 'Editor moet client-side nieuwe adressen kunnen toevoegen.');
adminCustomerAssert(strpos($html, 'button-outline') === false, 'Editoracties mogen geen slecht contrasterende outlineknoppen gebruiken.');
adminCustomerAssert(substr_count($html, 'button-secondary') >= 2, 'Contact- en adresknoppen moeten de zichtbare secundaire variant gebruiken.');

echo "AdminCustomer editor template tests passed.\n";
