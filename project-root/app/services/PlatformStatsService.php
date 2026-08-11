<?php
namespace app\services;

use app\core\Database;

class PlatformStatsService
{
    public static function summary(): array
    {
        $db = Database::connection();

        // Total AUM = every confirmed shareholding, valued at its company's current NAV
        $aum = $db->query(
            "SELECT COALESCE(SUM(s.shares * c.nav_per_share), 0) AS total
             FROM shareholdings s
             JOIN companies c ON c.id = s.company_id"
        )->fetch()['total'];

        $shareholders = $db->query(
            "SELECT COUNT(DISTINCT user_id) AS total FROM shareholdings"
        )->fetch()['total'];

        $pendingCount = $db->query(
            "SELECT COUNT(*) AS total FROM commitments WHERE status = 'pending'"
        )->fetch()['total'];

        $pendingValue = $db->query(
            "SELECT COALESCE(SUM(co.shares_requested * c.nav_per_share), 0) AS total
             FROM commitments co
             JOIN companies c ON c.id = co.company_id
             WHERE co.status = 'pending'"
        )->fetch()['total'];

        $companyCount = $db->query(
            "SELECT COUNT(*) AS total FROM companies WHERE status = 'active'"
        )->fetch()['total'];

        $totalArfBalance = $db->query(
            "SELECT COALESCE(SUM(arf_balance), 0) AS total FROM companies"
        )->fetch()['total'];

        return [
            'aum'             => (float) $aum,
            'shareholders'    => (int) $shareholders,
            'pendingCount'    => (int) $pendingCount,
            'pendingValue'    => (float) $pendingValue,
            'companyCount'    => (int) $companyCount,
            'totalArfBalance' => (float) $totalArfBalance,
        ];
    }
}
