<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminCustomer\Application\Query\CustomerListQuery;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerListItem;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerListResult;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerOverviewSummary;
use Flexgrid\Modules\AdminCustomer\Application\UseCase\ListCustomers;
use Flexgrid\Modules\AdminCustomer\Contract\CustomerListRepositoryInterface;
use Flexgrid\Modules\AdminCustomer\Service\CustomerOverviewPresenter;

final class MemoryCustomerListRepository implements CustomerListRepositoryInterface
{
    /** @var TenantId|null */ public $searchTenant;
    /** @var TenantId|null */ public $summaryTenant;

    public function search(TenantId $tenantId, CustomerListQuery $query): CustomerListResult
    {
        $this->searchTenant = $tenantId;
        return new CustomerListResult([
            new CustomerListItem(
                'customer-1', 'Acme', 'Acme B.V.', 'Ada Admin', 'ada@example.test',
                '030-1234567', 'Utrecht', 'NL', 'active', 1770000000
            ),
            new CustomerListItem(
                'customer-2', 'Historische klant', '', '', '', '', '', '', 'inactive', 1770000100
            ),
        ], 2, 1, 10);
    }

    public function getSummary(TenantId $tenantId): CustomerOverviewSummary
    {
        $this->summaryTenant = $tenantId;
        return new CustomerOverviewSummary(2, 1, 1);
    }
}

$query = new CustomerListQuery('Ada', 'active', 'primary_contact', 'desc', 1, 25);
adminCustomerAssert($query->getSearch() === 'Ada', 'Lijstquery moet zoekterm behouden.');
adminCustomerAssert($query->getSort() === 'primary_contact', 'Lijstquery moet whitelist-sortering behouden.');
adminCustomerAssertThrows(InvalidArgumentException::class, function (): void {
    new CustomerListQuery('', 'unknown');
}, 'Onbekende klantstatus moet worden geweigerd.');
adminCustomerAssertThrows(InvalidArgumentException::class, function (): void {
    new CustomerListQuery('', '', 'drop table');
}, 'Sorteerkolom buiten de whitelist moet worden geweigerd.');
adminCustomerAssertThrows(InvalidArgumentException::class, function (): void {
    new CustomerListQuery('', '', 'display_name', 'asc', 1, 11);
}, 'Niet-ondersteunde paginagrootte moet worden geweigerd.');

$tenantId = new TenantId('customer-list-tenant');
$repository = new MemoryCustomerListRepository();
$data = (new ListCustomers(new TenantContext($tenantId), $repository))->execute($query);
adminCustomerAssert($repository->searchTenant->equals($tenantId), 'Klantzoekopdracht moet TenantContext gebruiken.');
adminCustomerAssert($repository->summaryTenant->equals($tenantId), 'Klantensamenvatting moet TenantContext gebruiken.');

$viewModel = (new CustomerOverviewPresenter())->present($data, 'customer-refresh-event', '/Flexgrid/AdminCustomer/edit');
adminCustomerAssert(count($viewModel['summaryCards']) === 3, 'Presenter moet drie klantkaarten leveren.');
adminCustomerAssert(count($viewModel['table']['rows']) === 2, 'Presenter moet klanten naar tabelrijen vertalen.');
adminCustomerAssert(
    $viewModel['table']['rows'][0]['cells']['primary_contact']['secondary'] === 'ada@example.test · 030-1234567',
    'Primair contact moet e-mail en telefoon compact combineren.'
);
adminCustomerAssert(
    $viewModel['table']['rows'][1]['cells']['status']['badge'] === 'neutral',
    'Inactieve klant moet herkenbaar neutraal worden weergegeven.'
);
adminCustomerAssert($viewModel['refreshAction'] === 'customer-refresh-event', 'AJAX-eventnaam moet in het viewmodel staan.');
adminCustomerAssert(
    $viewModel['table']['rows'][0]['url'] === '/Flexgrid/AdminCustomer/edit/customer-1',
    'Klantregels moeten vanuit het overzicht naar de editor linken.'
);

echo "AdminCustomer list tests passed.\n";
