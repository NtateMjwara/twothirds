<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class CommercialActivity extends Model
{
    protected static string $table = 'commercial_activities';

    /**
     * The activity the company is currently running. This table is a history -
     * one row per period the asset spent doing a particular kind of work - so
     * "current" means the most recently opened row, not the only row.
     */
    public static function latestForCompany(int $companyId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT ca.*, att.name AS activity_name, att.slug AS activity_slug,
                    s.id AS sector_id, s.name AS sector_name, s.slug AS sector_slug
             FROM commercial_activities ca
             LEFT JOIN activity_types att ON att.id = ca.activity_type_id
             LEFT JOIN sectors s ON s.id = att.sector_id
             WHERE ca.company_id = ?
             ORDER BY ca.id DESC
             LIMIT 1"
        );
        $stmt->execute([$companyId]);
        return $stmt->fetch() ?: null;
    }
}
