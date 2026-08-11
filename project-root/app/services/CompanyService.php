<?php
namespace app\services;

use app\core\Database;
use app\models\Company;
use app\models\Asset;
use app\models\CommercialActivity;

class CompanyService
{
    // Derived, never stored: total issued minus everything already held minus
    // everything still pending. This is the only source of truth for availability.
    public static function sharesAvailable(int $companyId, int $sharesIssued): int
    {
        $db = Database::connection();

        $held = $db->prepare("SELECT COALESCE(SUM(shares), 0) AS total FROM shareholdings WHERE company_id = ?");
        $held->execute([$companyId]);
        $heldTotal = (int) $held->fetch()['total'];

        $pending = $db->prepare(
            "SELECT COALESCE(SUM(shares_requested), 0) AS total FROM commitments WHERE company_id = ? AND status = 'pending'"
        );
        $pending->execute([$companyId]);
        $pendingTotal = (int) $pending->fetch()['total'];

        return max(0, $sharesIssued - $heldTotal - $pendingTotal);
    }

    // One query for the whole discovery grid rather than one query per company (N+1).
    // Picks each company's most recent commercial activity for the card summary.
    public static function activeListings(): array
    {
        $db = Database::connection();
        $rows = $db->query(
            "SELECT c.id, c.reference, c.name, c.shares_issued, c.nav_per_share,
                    a.make, a.model,
                    ca.activity_type, ca.location
             FROM companies c
             LEFT JOIN assets a ON a.company_id = c.id
             LEFT JOIN commercial_activities ca ON ca.company_id = c.id
                AND ca.id = (SELECT MAX(id) FROM commercial_activities WHERE company_id = c.id)
             WHERE c.status = 'active'
             ORDER BY c.created_at DESC"
        )->fetchAll();

        foreach ($rows as &$row) {
            $row['shares_available'] = self::sharesAvailable((int) $row['id'], (int) $row['shares_issued']);
        }

        return $rows;
    }

    // All three inserts plus the audit log entry succeed together or not at all -
    // a half-created SPV (company row with no asset) is worse than a failed request.
    public static function createSpv(array $companyData, array $assetData, array $activityData, int $adminId): array
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $company = Company::createWithReference($companyData);

            $assetData['company_id'] = $company['id'];
            Asset::create($assetData);

            $activityData['company_id'] = $company['id'];
            CommercialActivity::create($activityData);

            $db->prepare(
                "INSERT INTO audit_log (actor_type, actor_id, action, entity_type, entity_id, details)
                 VALUES ('admin', ?, 'create_company', 'companies', ?, ?)"
            )->execute([$adminId, $company['id'], "Created {$companyData['name']} ({$company['reference']})"]);

            $db->commit();
            return $company;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
