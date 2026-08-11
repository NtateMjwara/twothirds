<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class CompanyDirector extends Model
{
    protected static string $table = 'company_directors';

    // Full history, including resigned directors - for the admin screen
    public static function forCompany(int $companyId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM company_directors WHERE company_id = ? ORDER BY appointed_date ASC"
        );
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }

    // Current board only - for the public company page
    public static function activeForCompany(int $companyId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM company_directors WHERE company_id = ? AND status = 'active' ORDER BY appointed_date ASC"
        );
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }
}
