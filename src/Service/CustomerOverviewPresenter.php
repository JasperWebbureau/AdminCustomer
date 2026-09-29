<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Service;

use Flexgrid\Modules\AdminCustomer\Application\Query\CustomerListQuery;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerListResult;
use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerOverviewSummary;
use Flexgrid\Modules\AdminCustomer\Domain\ValueObject\CustomerStatus;

final class CustomerOverviewPresenter
{
    public function present(array $data, string $refreshAction, string $editBaseUrl = ''): array
    {
        /** @var CustomerListQuery $query */
        $query = $data['query'];
        /** @var CustomerListResult $result */
        $result = $data['result'];
        /** @var CustomerOverviewSummary $summary */
        $summary = $data['summary'];

        $rows = [];
        foreach ($result->getItems() as $item) {
            $active = $item->getStatus() === CustomerStatus::ACTIVE;
            $contactSecondary = implode(' · ', array_filter([
                $item->getContactEmail(),
                $item->getContactPhone(),
            ], 'strlen'));
            $location = trim($item->getCity() . ($item->getCountryCode() !== '' ? ', ' . $item->getCountryCode() : ''));
            $rows[] = [
                'id' => $item->getPublicId(),
                'url' => $editBaseUrl !== '' ? rtrim($editBaseUrl, '/') . '/' . rawurlencode($item->getPublicId()) : '',
                'state' => $active ? '' : 'neutral',
                'classes' => $active ? [] : ['admin-customer-row--inactive'],
                'cells' => [
                    'display_name' => [
                        'value' => $item->getDisplayName(),
                        'secondary' => $item->getCompanyName(),
                        'title' => true,
                    ],
                    'primary_contact' => [
                        'value' => $item->getContactName() !== '' ? $item->getContactName() : '—',
                        'secondary' => $contactSecondary,
                    ],
                    'city' => $location !== '' ? $location : '—',
                    'created_at' => date('d-m-Y', $item->getCreatedAt()),
                    'status' => [
                        'value' => $active ? 'Actief' : 'Inactief',
                        'badge' => $active ? 'success' : 'neutral',
                    ],
                ],
            ];
        }

        $columns = [];
        foreach ([
            'display_name' => 'Klant',
            'primary_contact' => 'Primair contact',
            'city' => 'Vestigingsplaats',
            'created_at' => 'Aangemaakt',
            'status' => 'Status',
        ] as $key => $label) {
            $columns[] = [
                'key' => $key,
                'label' => $label,
                'sortable' => true,
                'sort_direction' => $query->getSort() === $key ? $query->getDirection() : '',
            ];
        }

        return [
            'refreshAction' => $refreshAction,
            'query' => $query,
            'result' => $result,
            'summaryCards' => [
                ['label' => 'Totaal klanten', 'value' => (string)$summary->getTotal(), 'meta' => 'in deze administratie', 'icon' => 'fas fa-address-book', 'tone' => 'accent'],
                ['label' => 'Actief', 'value' => (string)$summary->getActive(), 'meta' => 'beschikbaar voor nieuwe documenten', 'icon' => 'fas fa-user-check', 'tone' => 'success'],
                ['label' => 'Inactief', 'value' => (string)$summary->getInactive(), 'meta' => 'historie blijft behouden', 'icon' => 'fas fa-user-clock', 'tone' => 'neutral'],
            ],
            'table' => [
                'id' => 'admin-customer-overview',
                'label' => 'Klanten',
                'columns' => $columns,
                'rows' => $rows,
                'empty' => [
                    'title' => 'Geen klanten gevonden',
                    'message' => 'Pas de zoekterm of het statusfilter aan.',
                    'icon' => 'fas fa-address-book',
                ],
            ],
            'statusOptions' => [
                '' => 'Alle statussen',
                CustomerStatus::ACTIVE => 'Actief',
                CustomerStatus::INACTIVE => 'Inactief',
            ],
            'pageSizes' => CustomerListQuery::pageSizes(),
            'pages' => $this->pages($result),
        ];
    }

    /** @return int[] */
    private function pages(CustomerListResult $result): array
    {
        $total = $result->getTotalPages();
        $start = max(1, $result->getPage() - 2);
        $end = min($total, $start + 4);
        $start = max(1, $end - 4);
        return range($start, $end);
    }
}
