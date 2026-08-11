<?php
// Run every 15 minutes via cPanel Cron Jobs:
// php /home/youraccount/cron/retry_failed_emails.php

require __DIR__ . '/../app/bootstrap.php';

use app\core\Database;
use app\services\EmailQueueService;

$stmt = Database::connection()->query("SELECT id FROM email_queue WHERE status = 'pending'");
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($ids as $id) {
    EmailQueueService::attempt((int) $id);
}

echo count($ids) . " pending email(s) retried.\n";
