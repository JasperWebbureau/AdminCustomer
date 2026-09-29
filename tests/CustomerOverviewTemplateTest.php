<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/flexgrid/src/Html/Table/TrustedHtml.php';
require_once dirname(__DIR__, 3) . '/flexgrid/src/Html/Table/TableRenderer.php';

use Flexgrid\Modules\AdminCustomer\Application\Query\CustomerListQuery;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerListItem;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerListResult;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerOverviewSummary;
use Flexgrid\Modules\AdminCustomer\Service\CustomerOverviewPresenter;

if (!function_exists('t')) {
    function t(string $key, string $fallback): string
    {
        return $fallback;
    }
}

$queryObject = new CustomerListQuery('<zoek>', 'active');
$viewModel = (new CustomerOverviewPresenter())->present([
    'query' => $queryObject,
    'result' => new CustomerListResult([
        new CustomerListItem(
            'customer-template', '<script>alert(1)</script>', 'Veilig B.V.', 'Ada',
            'ada@example.test', '', 'Utrecht', 'NL', 'active', 1770000000
        ),
    ], 1, 1, 10),
    'summary' => new CustomerOverviewSummary(1, 1, 0),
], 'customer-refresh-event', '/Flexgrid/AdminCustomer/edit');

extract($viewModel, EXTR_OVERWRITE);
ob_start();
include dirname(__DIR__) . '/src/Templates/Customers/Content.php';
$html = (string)ob_get_clean();

adminCustomerAssert(strpos($html, 'class="admin-form admin-data-results') !== false, 'Overzicht moet de generieke admin-data-layout gebruiken.');
adminCustomerAssert(strpos($html, 'data-admin-customer-filters') !== false, 'Overzicht moet een stabiele AJAX-filtercontainer hebben.');
adminCustomerAssert(strpos($html, 'ajax="true"') !== false, 'Filterformulier moet de centrale Flexgrid AJAX-afhandeling gebruiken.');
adminCustomerAssert(strpos($html, 'action="customer-refresh-event"') !== false, 'Filterformulier moet de refresh-eventnaam gebruiken.');
adminCustomerAssert(strpos($html, 'data-fg-table="admin-customer-overview"') !== false, 'Overzicht moet de generieke TableRenderer gebruiken.');
adminCustomerAssert(strpos($html, '&lt;zoek&gt;') !== false, 'Zoekterm moet escaped worden teruggezet.');
adminCustomerAssert(strpos($html, '<script>alert(1)</script>') === false, 'Klantnaam mag geen HTML injecteren.');
adminCustomerAssert(strpos($html, '&lt;script&gt;alert(1)&lt;/script&gt;') !== false, 'Klantnaam moet zichtbaar maar escaped blijven.');
adminCustomerAssert(strpos($html, 'data-admin-customer-page="1"') !== false, 'Overzicht moet AJAX-paginering renderen.');
adminCustomerAssert(strpos($html, 'data-fg-table-row-url="/Flexgrid/AdminCustomer/edit/customer-template"') !== false, 'Klantregel moet naar de editor linken.');

$adminUi = (string)file_get_contents(dirname(__DIR__, 3) . '/flexgrid/src/Html/Admin/Css/AdminUi.scss');
adminCustomerAssert(strpos($adminUi, '.admin-data-toolbar') !== false, 'Herbruikbare overzichtstoolbar moet in AdminUi staan.');
adminCustomerAssert(strpos($adminUi, '.admin-data-pagination') !== false, 'Herbruikbare paginering moet in AdminUi staan.');

echo "AdminCustomer overview template tests passed.\n";
