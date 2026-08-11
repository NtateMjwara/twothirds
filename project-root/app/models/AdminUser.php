<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class AdminUser extends Model
{
    protected static string $table = 'admin_users';

    public static function findByEmail(string $email): ?array
    {
        $rows = static::where('email', $email);
        return $rows[0] ?? null;
    }

    public static function setResetToken(int $id, string $token, string $expiresAt): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE admin_users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?"
        );
        $stmt->execute([$token, $expiresAt, $id]);
    }

    public static function findByResetToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM admin_users WHERE reset_token = ? AND reset_token_expires_at > NOW() LIMIT 1"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function clearResetToken(int $id): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE admin_users SET reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
