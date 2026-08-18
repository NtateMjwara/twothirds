<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class CompanyUpdate extends Model
{
    protected static string $table = 'company_updates';

    /** Newest first — the timeline reads downward from the most recent event. */
    public static function forCompany(int $companyId, ?int $limit = null): array
    {
        $sql = "SELECT * FROM company_updates
                WHERE company_id = ?
                ORDER BY happened_on DESC, id DESC";
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare("DELETE FROM company_updates WHERE id = ?")->execute([$id]);
    }
}
