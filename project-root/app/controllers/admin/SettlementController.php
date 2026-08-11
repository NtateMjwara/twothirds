<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\models\Commitment;
use app\services\SettlementService;

class SettlementController extends AdminController
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->render('admin/settlement', ['queue' => Commitment::pendingQueue()]);
    }

    public function confirm(string $id): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        try {
            SettlementService::confirm((int) $id, (int) $_SESSION['admin_id']);
        } catch (\Throwable $e) {
            error_log('Settlement confirm failed: ' . $e->getMessage());
        }

        $this->redirect('/admin/settlement');
    }

    public function cancel(string $id): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        try {
            SettlementService::cancel((int) $id, (int) $_SESSION['admin_id']);
        } catch (\Throwable $e) {
            error_log('Settlement cancel failed: ' . $e->getMessage());
        }

        $this->redirect('/admin/settlement');
    }
}
