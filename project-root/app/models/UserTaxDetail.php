<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class UserTaxDetail extends Model
{
    protected static string $table = 'user_tax_details';

    public static function forUser(int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM user_tax_details WHERE user_id = ? LIMIT 1"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /** Insert or update, so the caller doesn't have to know which. */
    public static function save(int $userId, array $data): void
    {
        $existing = self::forUser($userId);

        if ($existing) {
            self::update((int) $existing['id'], $data);
            return;
        }

        self::create($data + ['user_id' => $userId]);
    }
}
