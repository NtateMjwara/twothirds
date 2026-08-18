<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class FinancialPeriod extends Model
{
    protected static string $table = 'financial_periods';

    public static function latestForCompany(int $companyId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM financial_periods WHERE company_id = ? ORDER BY period_start DESC LIMIT 1"
        );
        $stmt->execute([$companyId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function trailingTwelveMonths(int $companyId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT
                COALESCE(SUM(gross_revenue), 0)        AS revenue,
                COALESCE(SUM(operating_costs), 0)       AS costs,
                COALESCE(SUM(net_operating_income), 0)  AS profit
             FROM financial_periods
             WHERE company_id = ? AND period_start >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)"
        );
        $stmt->execute([$companyId]);
        return $stmt->fetch();
    }
    
    /** Newest first — the financials tab reads downward from the latest period. */
    public static function forCompany(int $companyId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM financial_periods
             WHERE company_id = ?
             ORDER BY period_start DESC"
        );
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }    
}
