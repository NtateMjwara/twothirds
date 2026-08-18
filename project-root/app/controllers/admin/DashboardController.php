<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\core\Database;
use app\services\PlatformStatsService;

class DashboardController extends AdminController
{
    public function index(): void
    {
        $this->requirePermission('dashboard.view');

        $this->render('admin/dashboard', [
            'stats'  => PlatformStatsService::summary(),
            // Counts for the queues this role can actually work, so the first
            // screen after logging in says whether there is anything to do.
            'queues' => $this->queues(),
        ]);
    }

    private function queues(): array
    {
        $db = Database::connection();
        $queues = [];

        if ($this->can('kyc.view')) {
            $queues[] = [
                'label' => 'KYC submissions to review',
                'href'  => '/admin/kyc',
                'icon'  => 'ti-shield-check',
                'count' => (int) $db->query("SELECT COUNT(*) FROM user_kyc WHERE status = 'pending'")->fetchColumn(),
            ];
        }

        if ($this->can('settlement.view')) {
            $queues[] = [
                'label' => 'Commitments awaiting settlement',
                'href'  => '/admin/settlement',
                'icon'  => 'ti-cash',
                'count' => (int) $db->query("SELECT COUNT(*) FROM commitments WHERE status = 'pending'")->fetchColumn(),
            ];
        }

        if ($this->can('email.view')) {
            $queues[] = [
                'label' => 'Emails failed or unsent',
                'href'  => '/admin/email-queue',
                'icon'  => 'ti-mail',
                'count' => (int) $db->query("SELECT COUNT(*) FROM email_queue WHERE status != 'sent'")->fetchColumn(),
            ];
        }

        return $queues;
    }
}
