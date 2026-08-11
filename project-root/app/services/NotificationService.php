<?php
namespace app\services;

use app\core\Database;
use app\models\Notification;
use app\models\User;

class NotificationService
{
    public static function notify(int $userId, string $type, string $message, ?string $emailSubject = null, ?string $emailBody = null): void
    {
        Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'payload' => $message,
        ]);

        if ($emailSubject && $emailBody) {
            $user = User::find($userId);
            if ($user) {
                EmailQueueService::send($user['email'], '', $emailSubject, $emailBody);
            }
        }
    }

    public static function notifyShareholders(int $companyId, string $type, string $message, ?string $emailSubject = null, ?string $emailBody = null): void
    {
        $stmt = Database::connection()->prepare(
            "SELECT DISTINCT user_id FROM shareholdings WHERE company_id = ?"
        );
        $stmt->execute([$companyId]);

        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $userId) {
            self::notify((int) $userId, $type, $message, $emailSubject, $emailBody);
        }
    }
}
