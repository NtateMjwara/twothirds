<?php
// Run once daily via cPanel Cron Jobs:
// php /home/youraccount/cron/cleanup_login_attempts.php
// Only deletes rows old enough to be irrelevant to the 15-minute lockout window -
// safe to run alongside active lockouts without affecting them.

require __DIR__ . '/../app/bootstrap.php';

use app\core\Database;

$stmt = Database::connection()->prepare(
    "DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
);
$stmt->execute();

echo $stmt->rowCount() . " old login attempt row(s) deleted.\n";
