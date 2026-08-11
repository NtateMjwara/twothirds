<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class Shareholding extends Model
{
    protected static string $table = 'shareholdings';

    // This ledger is append-only. A correction is a new offsetting row,
    // never an edit to history - so updating one is a programming error, not a valid action.
    public static function update(int $id, array $data): bool
    {
        throw new \RuntimeException('Shareholdings are append-only and cannot be updated.');
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT s.*, c.name AS company_name, c.reference AS company_reference, c.nav_per_share
             FROM shareholdings s
             JOIN companies c ON c.id = s.company_id
             WHERE s.user_id = ?
             ORDER BY s.settled_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
