<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Controller;

use Flexgrid\Event\AjaxEvent;
use Flexgrid\Flexgrid;
use Flexgrid\Modules\AdminCustomer\Application\Command\CreateCustomerCommand;
use Flexgrid\Modules\AdminCustomer\Application\Command\UpdateCustomerCommand;
use Flexgrid\Modules\AdminCustomer\Application\Query\CustomerListQuery;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerDetailContext;
use Flexgrid\Modules\AdminCustomer\Domain\Model\Customer;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\AddressType;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\CustomerStatus;
use Flexgrid\Modules\AdminCustomer\Exception\CustomerNotFoundException;
use Flexgrid\Modules\AdminCustomer\Service\AdminCustomerFactory;
use Flexgrid\Modules\AdminCustomer\Service\CustomerDetailExtensionLoader;
use Flexgrid\Response\AjaxResponse;
use Flexgrid\Response\PageResponse;
use Flexgrid\Response\TemplateResponse;
use Flexgrid\Utils\Request\Request;

/**
 * @FG\Controller [name=AdminCustomer,type=Module,icon=fas fa-address-book,level=2,administrationPanel=true,administrationLabel=Klanten,administrationRoute=customers,administrationPriority=20]
 */
final class AdminCustomerController
{
    public function index()
    {
        return $this->customers();
    }

    public function customers()
    {
        appendIconAndTitleToHeader('fas fa-address-book', 'Klanten', 'Administratie');
        $this->registerAssets(false, true);
        $this->appendPageActions('overview');

        return new TemplateResponse('Flexgrid/Modules/AdminCustomer/src/Templates/Customers/Index.php', [
            'content' => (string)$this->renderContent($this->queryFromRequest()),
            'createUrl' => $this->moduleUrl('create'),
        ]);
    }

    public function create()
    {
        appendIconAndTitleToHeader('fas fa-user-plus', 'Nieuwe klant', 'Administratie');
        $this->registerAssets();
        $this->appendPageActions('back');

        return new TemplateResponse('Flexgrid/Modules/AdminCustomer/src/Templates/Create/Index.php', [
            'storeAction' => $this->ajaxAction('store'),
            'overviewUrl' => $this->moduleUrl('customers'),
        ]);
    }

    public function store(): AjaxResponse
    {
        try {
            $request = new Request();
            $customer = AdminCustomerFactory::createCreateCustomer()->execute(new CreateCustomerCommand(
                $this->requestString($request, 'display_name'),
                $this->requestString($request, 'company_name'),
                $this->requestString($request, 'registration_number'),
                $this->requestString($request, 'tax_number'),
                [],
                [],
                $this->requestString($request, 'notes')
            ));
            $response = new AjaxResponse();
            $response->success = true;
            $response->redirect = $this->moduleUrl('edit/' . rawurlencode($customer->getPublicId()));
            return $response;
        } catch (\Throwable $throwable) {
            return $this->errorResponse($throwable);
        }
    }

    public function edit($args = [])
    {
        try {
            $customer = AdminCustomerFactory::createGetCustomer()->execute($this->routeArgument($args));
        } catch (\Throwable $throwable) {
            return $this->customers();
        }

        appendIconAndTitleToHeader('fas fa-user', $customer->getDisplayName(), 'Klanten');
        $this->registerAssets(true);
        $this->appendPageActions('back');
        $viewModel = AdminCustomerFactory::createEditorPresenter()->present(
            $customer,
            $this->ajaxAction('update')
        );
        $detailExtensions = $this->renderDetailExtensions($customer);

        return new TemplateResponse('Flexgrid/Modules/AdminCustomer/src/Templates/Editor/Index.php', [
            'content' => (string)new TemplateResponse(
                'Flexgrid/Modules/AdminCustomer/src/Templates/Editor/Content.php',
                $viewModel
            ),
            'displayName' => $viewModel['displayName'],
            'status' => $viewModel['status'],
            'overviewUrl' => $this->moduleUrl('customers'),
            'detailExtensions' => $detailExtensions,
        ]);
    }

    public function update(): AjaxResponse
    {
        try {
            $request = new Request();
            $customer = AdminCustomerFactory::createUpdateCustomer()->execute(new UpdateCustomerCommand(
                $this->requestString($request, 'public_id'),
                $this->requestString($request, 'display_name'),
                $this->requestString($request, 'company_name'),
                $this->requestString($request, 'registration_number'),
                $this->requestString($request, 'tax_number'),
                $this->requestString($request, 'status'),
                $this->requestString($request, 'notes'),
                $this->requestRows($request, 'contacts'),
                $this->requestRows($request, 'addresses')
            ));
            return $this->editorSuccessResponse($customer);
        } catch (\Throwable $throwable) {
            return $this->errorResponse($throwable);
        }
    }

    public function refresh(): AjaxResponse
    {
        $query = $this->queryFromRequest();
        $response = new AjaxResponse();
        $response->success = true;
        $response->setContainer(
            '[data-admin-customer-content]',
            (string)$this->renderContent($query)
        );
        $response->replaceUrl = $this->overviewUrl($query);

        return $response;
    }

    private function renderContent(CustomerListQuery $query): TemplateResponse
    {
        $data = AdminCustomerFactory::createListCustomers()->execute($query);
        $viewModel = AdminCustomerFactory::createOverviewPresenter()->present(
            $data,
            $this->refreshAction(),
            $this->moduleUrl('edit')
        );

        return new TemplateResponse('Flexgrid/Modules/AdminCustomer/src/Templates/Customers/Content.php', $viewModel);
    }

    private function queryFromRequest(): CustomerListQuery
    {
        $request = new Request();
        $status = strtolower($this->requestString($request, 'status'));
        $sort = strtolower($this->requestString($request, 'sort'));
        $direction = strtolower($this->requestString($request, 'direction'));
        $perPage = $request->getInt('per_page');

        return new CustomerListQuery(
            substr($this->requestString($request, 'q'), 0, 120),
            in_array($status, CustomerStatus::values(), true) ? $status : '',
            in_array($sort, CustomerListQuery::sorts(), true) ? $sort : 'display_name',
            in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc',
            max(1, $request->getInt('page')),
            in_array($perPage, CustomerListQuery::pageSizes(), true) ? $perPage : 10
        );
    }

    private function requestString(Request $request, string $key): string
    {
        $value = $request->get($key, '');
        if (!is_string($value) && !is_int($value)) {
            return '';
        }
        return trim(strip_tags((string)$value));
    }

    private function requestRows(Request $request, string $key): array
    {
        $rows = $request->get($key, []);
        if (!is_array($rows)) {
            return [];
        }
        $clean = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                $clean[] = $row;
                continue;
            }
            $cleanRow = [];
            foreach ($row as $name => $value) {
                if ((is_string($name) || is_int($name)) && (is_string($value) || is_int($value))) {
                    $cleanRow[(string)$name] = trim(strip_tags((string)$value));
                }
            }
            $clean[] = $cleanRow;
        }
        return $clean;
    }

    private function refreshAction(): string
    {
        $event = new AjaxEvent(self::class, 'refresh');
        $event->setMinimumAccessLevel(2);
        return $event->getName();
    }

    private function ajaxAction(string $method): string
    {
        $event = new AjaxEvent(self::class, $method);
        $event->setMinimumAccessLevel(2);
        return $event->getName();
    }

    private function routeArgument($args): string
    {
        $value = is_array($args) ? ($args[0] ?? '') : $args;
        return is_string($value) || is_int($value) ? trim((string)$value) : '';
    }

    private function editorSuccessResponse(Customer $customer): AjaxResponse
    {
        $viewModel = AdminCustomerFactory::createEditorPresenter()->present(
            $customer,
            $this->ajaxAction('update')
        );
        $response = new AjaxResponse();
        $response->success = true;
        $response->notifications = [
            '<div class="notification notification--success" fade="2400">Klant opgeslagen.</div>',
        ];
        $response->setContainer(
            '[data-admin-customer-editor]',
            (string)new TemplateResponse(
                'Flexgrid/Modules/AdminCustomer/src/Templates/Editor/Content.php',
                $viewModel
            )
        );
        $response->setContainer(
            '[data-admin-customer-name]',
            htmlspecialchars($customer->getDisplayName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
        $response->setContainer(
            '[data-admin-customer-detail-extensions]',
            $this->renderDetailExtensions($customer)
        );
        return $response;
    }

    private function renderDetailExtensions(Customer $customer): string
    {
        return (new CustomerDetailExtensionLoader(dirname(__DIR__, 3)))->render(
            new CustomerDetailContext(
                $customer->getPublicId(),
                $customer->getDisplayName(),
                $customer->getStatus()->getValue(),
                $customer->getPrimaryAddress(new AddressType(AddressType::BILLING)) !== null
            )
        );
    }

    private function errorResponse(\Throwable $throwable): AjaxResponse
    {
        $response = new AjaxResponse();
        $response->success = false;
        $message = $throwable instanceof \InvalidArgumentException
            || $throwable instanceof \DomainException
            || $throwable instanceof CustomerNotFoundException
            ? $throwable->getMessage()
            : 'De klant kon niet worden opgeslagen.';
        $response->error = $message;
        $response->notifications = [
            '<div class="notification notification--error" fade="6000">'
            . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</div>',
        ];
        return $response;
    }

    private function registerAssets(bool $editor = false, bool $overview = false): void
    {
        PageResponse::addAsset('Flexgrid/Flexgrid/src/Html/Admin/Css/AdminUi.scss');
        PageResponse::addAsset('Flexgrid/Flexgrid/src/Html/Table/Css/Table.scss');
        PageResponse::addAsset('Flexgrid/Flexgrid/src/Html/Table/Js/Table.js');
        if ($overview) {
            PageResponse::addAsset('Flexgrid/Modules/AdminCustomer/src/Templates/Customers/Css/Customers.scss');
            PageResponse::addAsset('Flexgrid/Modules/AdminCustomer/src/Templates/Customers/Js/Customers.js');
        }
        if ($editor) {
            PageResponse::addAsset('Flexgrid/Modules/AdminCustomer/src/Templates/Editor/Css/Editor.scss');
            PageResponse::addAsset('Flexgrid/Modules/AdminCustomer/src/Templates/Editor/Js/Editor.js');
        }
    }

    private function appendPageActions(string $mode): void
    {
        \Flexgrid\Modules\AdminCore\Service\AdminHeader::AdminAddHeader([new TemplateResponse(
            'Flexgrid/Modules/AdminCustomer/src/Templates/HeaderActions.php',
            ['mode' => $mode, 'createUrl' => $this->moduleUrl('create'), 'overviewUrl' => $this->moduleUrl('customers')]
        )]);
    }

    private function moduleUrl(string $path): string
    {
        return rtrim(__DOMAIN__, '/') . '/Flexgrid/AdminCustomer/' . ltrim($path, '/');
    }

    private function overviewUrl(CustomerListQuery $query): string
    {
        $parameters = array_filter([
            'q' => $query->getSearch(),
            'status' => $query->getStatus(),
            'sort' => $query->getSort(),
            'direction' => $query->getDirection(),
            'page' => $query->getPage(),
            'per_page' => $query->getPerPage(),
        ], function ($value): bool {
            return $value !== '' && $value !== null;
        });

        return rtrim(__DOMAIN__, '/') . '/Flexgrid/AdminCustomer/customers?' . http_build_query($parameters);
    }
}
