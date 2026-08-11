<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class UserAddress extends Model
{
    protected static string $table = 'user_addresses';

    public static function forUser(int $userId): array
    {
        return static::where('user_id', $userId);
    }

    public static function forUserAndType(int $userId, string $type): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM user_addresses WHERE user_id = ? AND address_type = ? LIMIT 1"
        );
        $stmt->execute([$userId, $type]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
