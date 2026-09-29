<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerDetailContext;
use Flexgrid\Modules\AdminCustomer\Contract\CustomerDetailExtensionInterface;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\CustomerStatus;
use Flexgrid\Modules\AdminCustomer\Service\CustomerDetailExtensionLoader;

final class CustomerTestDetailExtension implements CustomerDetailExtensionInterface
{
    public function render(CustomerDetailContext $context): string
    {
        return '<aside data-test-customer-extension>'
            . htmlspecialchars($context->getDisplayName(), ENT_QUOTES, 'UTF-8')
            . '</aside>';
    }
}

$context = new CustomerDetailContext(
    'customer-extension-test',
    '<Klant B.V.>',
    CustomerStatus::ACTIVE,
    true
);
$loader = new CustomerDetailExtensionLoader(__DIR__ . '/missing-modules', [CustomerTestDetailExtension::class]);
$html = $loader->render($context);

adminCustomerAssert(strpos($html, 'data-test-customer-extension') !== false, 'Klantdetailextensie moet door de generieke loader worden gerenderd.');
adminCustomerAssert(strpos($html, '&lt;Klant B.V.&gt;') !== false, 'Extensiecontext moet veilig te renderen zijn.');
adminCustomerAssert($context->isActive(), 'Actieve klantcontext moet als actief herkenbaar zijn.');
adminCustomerAssert($context->hasPrimaryBillingAddress(), 'Klantcontext moet de factuuradrescapability delen.');

$discovery = new CustomerDetailExtensionLoader(dirname(__DIR__, 2));
$classesMethod = new ReflectionMethod($discovery, 'classes');
$classesMethod->setAccessible(true);
adminCustomerAssert(
    in_array(
        'Flexgrid\\Modules\\AdminInvoice\\Integration\\Customer\\CustomerDetailExtension',
        $classesMethod->invoke($discovery),
        true
    ),
    'Conventiegebaseerde ontdekking moet de geinstalleerde Invoice-extensie vinden.'
);

$inactive = new CustomerDetailContext(
    'customer-extension-inactive',
    'Inactieve klant',
    CustomerStatus::INACTIVE,
    true
);
adminCustomerAssert(!$inactive->isActive(), 'Inactieve klantcontext mag niet als actief worden aangeboden.');

$controller = (string)file_get_contents(dirname(__DIR__) . '/src/Controller/AdminCustomerController.php');
adminCustomerAssert(
    strpos($controller, 'Flexgrid\\Modules\\AdminInvoice\\') === false,
    'AdminCustomer-controller mag geen concrete Invoice-module importeren.'
);
$template = (string)file_get_contents(dirname(__DIR__) . '/src/Templates/Editor/Index.php');
adminCustomerAssert(
    strpos($template, 'data-admin-customer-detail-extensions') !== false,
    'Klantdetail mist de generieke extensieslot.'
);
adminCustomerAssert(
    strpos($controller, "'[data-admin-customer-detail-extensions]'") !== false,
    'Een AJAX-save moet de klantdetailextensies met de actuele status en adrescapability verversen.'
);

echo "AdminCustomer detail extension tests passed.\n";
