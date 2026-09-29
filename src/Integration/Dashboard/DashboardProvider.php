<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Integration\Dashboard;

use Flexgrid\Modules\AdminDashboard\Contract\DashboardProviderInterface;
use Flexgrid\Modules\AdminDashboard\Provider\AbstractPdoDashboardProvider;

final class DashboardProvider extends AbstractPdoDashboardProvider implements DashboardProviderInterface
{
    public function getDashboardContribution(): array
    {
        $tenant = $this->tenant->toString();
        $countStatement = $this->connection->prepare("SELECT COUNT(*) FROM `admin_customer` WHERE `tenant_id`=:tenant AND `status`='active'");
        $countStatement->execute([':tenant' => $tenant]);
        $activeCount = (int)$countStatement->fetchColumn();

        $incompleteStatement = $this->connection->prepare("SELECT COUNT(*) FROM `admin_customer` c WHERE c.`tenant_id`=:tenant AND c.`status`='active' AND (NOT EXISTS (SELECT 1 FROM `admin_customer_address` a WHERE a.`tenant_id`=c.`tenant_id` AND a.`customer_id`=c.`id` AND a.`is_primary`=1) OR NOT EXISTS (SELECT 1 FROM `admin_customer_contact` p WHERE p.`tenant_id`=c.`tenant_id` AND p.`customer_id`=c.`id` AND p.`is_primary`=1))");
        $incompleteStatement->execute([':tenant' => $tenant]);
        $incompleteCount = (int)$incompleteStatement->fetchColumn();

        $recent = $this->connection->prepare("SELECT `public_id`,`display_name`,`created_at` FROM `admin_customer` WHERE `tenant_id`=:tenant ORDER BY `created_at` DESC,`id` DESC LIMIT 2");
        $recent->execute([':tenant' => $tenant]);
        $activities = [];
        foreach ($recent->fetchAll(\PDO::FETCH_ASSOC) as $customer) {
            $activities[] = ['id' => 'customer-' . $customer['public_id'], 'title' => 'Klant toegevoegd', 'description' => (string)$customer['display_name'], 'icon' => 'fas fa-user-plus', 'tone' => 'info', 'href' => $this->url('AdminCustomer', 'edit/' . rawurlencode((string)$customer['public_id'])), 'timestamp' => (int)$customer['created_at'], 'time' => $this->activityTime((int)$customer['created_at'])];
        }

        return [
            'activities' => $activities,
            'quick_actions' => [['id' => 'create-customer', 'label' => 'Nieuwe klant', 'icon' => 'fas fa-user-plus', 'tone' => 'info', 'href' => $this->url('AdminCustomer', 'create'), 'priority' => 30]],
            'attention' => $incompleteCount > 0 ? [['id' => 'incomplete-customers', 'title' => $incompleteCount . ' onvolledige klant' . ($incompleteCount === 1 ? '' : 'en'), 'description' => 'Primair adres of primair contact ontbreekt.', 'icon' => 'fas fa-address-card', 'tone' => 'warning', 'badge' => 'Aanvullen', 'badge_tone' => 'neutral', 'href' => $this->url('AdminCustomer', 'customers'), 'priority' => 30]] : [],
            'modules' => [['id' => 'admin-customer', 'title' => 'Klanten', 'description' => $activeCount . ' actief', 'icon' => 'fas fa-address-book', 'status' => 'Actief', 'tone' => 'info', 'href' => $this->url('AdminCustomer', 'customers'), 'priority' => 20]],
        ];
    }
}
