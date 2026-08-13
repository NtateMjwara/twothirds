<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class Watchlist extends Model
{
    protected static string $table = 'watchlist';

    public static function isWatching(int $userId, int $companyId): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT 1 FROM watchlist WHERE user_id = ? AND company_id = ? LIMIT 1"
        );
        $stmt->execute([$userId, $companyId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Every company this user has saved, as a lookup set keyed by company id.
     * The discovery grid needs to know the saved state of a page of listings at
     * once; one query beats isWatching() per card.
     */
    public static function companyIdsForUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT company_id FROM watchlist WHERE user_id = ?"
        );
        $stmt->execute([$userId]);

        return array_flip(array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN)));
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT w.*, c.reference, c.name, c.nav_per_share
             FROM watchlist w
             JOIN companies c ON c.id = w.company_id
             WHERE w.user_id = ?
             ORDER BY w.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function remove(int $userId, int $companyId): void
    {
        Database::connection()->prepare(
            "DELETE FROM watchlist WHERE user_id = ? AND company_id = ?"
        )->execute([$userId, $companyId]);
    }
}
