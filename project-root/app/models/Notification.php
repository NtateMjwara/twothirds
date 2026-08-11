<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class Notification extends Model
{
    protected static string $table = 'notifications';

    public static function forUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function unreadCount(int $userId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND read_at IS NULL"
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetch()['c'];
    }

    public static function markRead(int $id, int $userId): void
    {
        Database::connection()->prepare(
            "UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ? AND read_at IS NULL"
        )->execute([$id, $userId]);
    }

    public static function markAllRead(int $userId): void
    {
        Database::connection()->prepare(
            "UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL"
        )->execute([$userId]);
    }
}
