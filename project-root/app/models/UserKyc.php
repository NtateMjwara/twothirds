<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class UserKyc extends Model
{
    protected static string $table = 'user_kyc';

    public static function forUser(int $userId): ?array
    {
        $rows = static::where('user_id', $userId);
        return $rows[0] ?? null;
    }

    public static function pendingQueue(): array
    {
        $stmt = Database::connection()->query(
            "SELECT k.*, u.email AS user_email, p.first_name, p.last_name
             FROM user_kyc k
             JOIN users u ON u.id = k.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             WHERE k.status = 'pending'
             ORDER BY k.updated_at ASC"
        );
        return $stmt->fetchAll();
    }
}
