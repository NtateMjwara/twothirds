<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class UserBankAccount extends Model
{
    protected static string $table = 'user_bank_accounts';

    /** Every account, primary first, then newest. */
    public static function forUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM user_bank_accounts
             WHERE user_id = ?
             ORDER BY is_primary DESC, created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function primaryForUser(int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM user_bank_accounts
             WHERE user_id = ?
             ORDER BY is_primary DESC, created_at DESC
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /** Scoped by user, so an id from a URL can't reach someone else's account. */
    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM user_bank_accounts WHERE id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function countForCurrency(int $userId, string $currency): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM user_bank_accounts WHERE user_id = ? AND currency = ?"
        );
        $stmt->execute([$userId, $currency]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Exactly one primary per user, enforced here rather than by a constraint.
     * MySQL has no partial unique index, so UNIQUE(user_id, is_primary) would
     * also stop a user having two non-primary accounts.
     */
    public static function makePrimary(int $id, int $userId): void
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE user_bank_accounts SET is_primary = 0 WHERE user_id = ?")->execute([$userId]);
            $db->prepare("UPDATE user_bank_accounts SET is_primary = 1 WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function deleteForUser(int $id, int $userId): void
    {
        Database::connection()
            ->prepare("DELETE FROM user_bank_accounts WHERE id = ? AND user_id = ?")
            ->execute([$id, $userId]);
    }
}
