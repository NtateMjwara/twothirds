<?php
namespace app\services;

use app\core\Database;

class EmailQueueService
{
    private const MAX_ATTEMPTS = 5;

    // Every call site that used to call Mailer::send() directly should call this instead.
    // It logs the attempt before sending, so a failure is tracked, not just error_log()'d.
    public static function send(string $toEmail, string $toName, string $subject, string $body): void
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO email_queue (to_email, to_name, subject, body, status) VALUES (?, ?, ?, ?, 'pending')"
        );
        $stmt->execute([$toEmail, $toName, $subject, $body]);

        self::attempt((int) Database::connection()->lastInsertId());
    }

    // Also called by the retry cron job for anything still pending from a prior failure.
    public static function attempt(int $queueId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare("SELECT * FROM email_queue WHERE id = ?");
        $stmt->execute([$queueId]);
        $row = $stmt->fetch();

        if (!$row || $row['status'] === 'sent') {
            return;
        }

        $sent = Mailer::send($row['to_email'], $row['to_name'] ?? '', $row['subject'], $row['body']);
        $attempts = (int) $row['attempts'] + 1;

        if ($sent) {
            $db->prepare("UPDATE email_queue SET status = 'sent', attempts = ?, last_attempted_at = NOW() WHERE id = ?")
               ->execute([$attempts, $queueId]);
            return;
        }

        $status = $attempts >= self::MAX_ATTEMPTS ? 'failed' : 'pending';
        $db->prepare(
            "UPDATE email_queue SET status = ?, attempts = ?, last_attempted_at = NOW(), last_error = 'Send failed' WHERE id = ?"
        )->execute([$status, $attempts, $queueId]);
    }
}
