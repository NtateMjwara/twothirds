<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        $rows = static::where('email', $email);
        return $rows[0] ?? null;
    }

    public static function setVerificationToken(int $id, string $token, string $expiresAt): void
    {
        Database::connection()->prepare(
            "UPDATE users SET verification_token = ?, verification_token_expires_at = ? WHERE id = ?"
        )->execute([$token, $expiresAt, $id]);
    }

    public static function findByVerificationToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM users WHERE verification_token = ? AND verification_token_expires_at > NOW() LIMIT 1"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function markEmailVerified(int $id): void
    {
        Database::connection()->prepare(
            "UPDATE users SET email_verified_at = NOW(), verification_token = NULL, verification_token_expires_at = NULL WHERE id = ?"
        )->execute([$id]);
    }

    public static function setResetToken(int $id, string $token, string $expiresAt): void
    {
        Database::connection()->prepare(
            "UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?"
        )->execute([$token, $expiresAt, $id]);
    }

    public static function findByResetToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW() LIMIT 1"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function clearResetToken(int $id): void
    {
        Database::connection()->prepare(
            "UPDATE users SET reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?"
        )->execute([$id]);
    }
}
