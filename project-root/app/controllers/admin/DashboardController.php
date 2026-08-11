<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\services\PlatformStatsService;

class DashboardController extends AdminController
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->render('admin/dashboard', [
            'stats' => PlatformStatsService::summary(),
        ]);
    }
}
