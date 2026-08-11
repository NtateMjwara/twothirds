<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\core\Database;

class EmailQueueController extends AdminController
{
    public function index(): void
    {
        $this->requireAdmin();

        $stmt = Database::connection()->query(
            "SELECT * FROM email_queue WHERE status != 'sent' ORDER BY created_at DESC LIMIT 100"
        );

        $this->render('admin/email-queue', ['queue' => $stmt->fetchAll()]);
    }
}
