<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\models\UserKyc;
use app\services\KycReviewService;

class KycController extends AdminController
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->render('admin/kyc', ['queue' => UserKyc::pendingQueue()]);
    }

    public function approve(string $id): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        try {
            KycReviewService::approve((int) $id, (int) $_SESSION['admin_id']);
        } catch (\Throwable $e) {
            error_log('KYC approve failed: ' . $e->getMessage());
        }

        $this->redirect('/admin/kyc');
    }

    public function reject(string $id): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $reason = trim($_POST['reason'] ?? '') ?: 'Not specified';

        try {
            KycReviewService::reject((int) $id, (int) $_SESSION['admin_id'], $reason);
        } catch (\Throwable $e) {
            error_log('KYC reject failed: ' . $e->getMessage());
        }

        $this->redirect('/admin/kyc');
    }
}
