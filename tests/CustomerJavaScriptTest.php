<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$javascript = (string)file_get_contents(
    dirname(__DIR__) . '/src/Templates/Customers/Js/Customers.js'
);

adminCustomerAssert(
    strpos($javascript, 'class AdminCustomerOverview') !== false,
    'Klantoverzichtinteractie moet als JavaScript-class zijn opgebouwd.'
);
adminCustomerAssert(
    strpos($javascript, 'requestSubmit()') !== false,
    'Klantoverzicht moet het declaratieve Flexgrid AJAX-formulier submitten.'
);
adminCustomerAssert(
    strpos($javascript, '$(') === false && strpos($javascript, 'jQuery') === false,
    'AdminCustomer mag niet direct van jQuery afhankelijk zijn.'
);
adminCustomerAssert(
    strpos($javascript, 'fetch(') === false && strpos($javascript, 'XMLHttpRequest') === false,
    'AdminCustomer mag geen eigen AJAX-transport naast Flexgrid introduceren.'
);
adminCustomerAssert(
    strpos($javascript, "setValue(form, 'status', '')") !== false,
    'Filters wissen moet ook het klantstatusfilter terugzetten.'
);

$editorJavaScript = (string)file_get_contents(
    dirname(__DIR__) . '/src/Templates/Editor/Js/Editor.js'
);
adminCustomerAssert(
    strpos($editorJavaScript, 'class AdminCustomerEditor') !== false,
    'Klanteditorinteractie moet als JavaScript-class zijn opgebouwd.'
);
adminCustomerAssert(
    strpos($editorJavaScript, 'ensureAddressPrimaries()') !== false
    && strpos($editorJavaScript, 'uncheckAddressType') !== false,
    'Editor moet één primair adres per type client-side bewaken.'
);
adminCustomerAssert(
    strpos($editorJavaScript, '$(') === false
    && strpos($editorJavaScript, 'jQuery') === false
    && strpos($editorJavaScript, 'fetch(') === false
    && strpos($editorJavaScript, 'XMLHttpRequest') === false,
    'Klanteditor mag geen directe jQuery- of eigen AJAX-transportafhankelijkheid hebben.'
);

$controller = (string)file_get_contents(
    dirname(__DIR__) . '/src/Controller/AdminCustomerController.php'
);
adminCustomerAssert(
    strpos($controller, "PageResponse::addAsset('Flexgrid/Modules/AdminCustomer/src/Templates/Editor/Js/Editor.js')") !== false
    && strpos($controller, "PageResponse::addAsset('Flexgrid/Modules/AdminCustomer/src/Templates/Editor/Css/Editor.scss')") !== false,
    'De klanteditorassets moeten expliciet worden geregistreerd en mogen niet van een template-cache-entry afhangen.'
);
adminCustomerAssert(
    strpos($controller, "PageResponse::addAsset('Flexgrid/Modules/AdminCustomer/src/Templates/Customers/Js/Customers.js')") !== false
    && strpos($controller, "PageResponse::addAsset('Flexgrid/Modules/AdminCustomer/src/Templates/Customers/Css/Customers.scss')") !== false,
    'De klantoverzichtassets moeten expliciet worden geregistreerd.'
);

echo "AdminCustomer JavaScript tests passed.\n";
