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
        return self::filteredListings([]);
    }

    // Same grid, but narrowed by whatever the discovery page's filter form sent in.
    // Every clause is optional - an empty/absent value in $filters is a no-op.
    // Supported keys: q, category, location, asset_type, min_price, max_price, sort.
    public static function filteredListings(array $filters): array
    {
        $db = Database::connection();

        $sql = "SELECT c.id, c.reference, c.name, c.shares_issued, c.nav_per_share,
                       a.make, a.model,
                       ca.activity_type, ca.location
                FROM companies c
                LEFT JOIN assets a ON a.company_id = c.id
                LEFT JOIN commercial_activities ca ON ca.company_id = c.id
                   AND ca.id = (SELECT MAX(id) FROM commercial_activities WHERE company_id = c.id)
                WHERE c.status = 'active'";
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            // Three distinct placeholders for one value, not :q reused three times -
            // native (non-emulated) mysqlnd prepares don't support repeating a named
            // parameter within the same query.
            $sql .= " AND (c.name LIKE :q1 OR a.make LIKE :q2 OR a.model LIKE :q3)";
            $params['q1'] = $params['q2'] = $params['q3'] = "%{$q}%";
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $sql .= " AND ca.activity_type = :category";
            $params['category'] = $category;
        }

        $location = trim((string) ($filters['location'] ?? ''));
        if ($location !== '') {
            $sql .= " AND ca.location = :location";
            $params['location'] = $location;
        }

        // "Asset type" on this platform is the vehicle make (Toyota, VW, Ford, ...) -
        // there's no separate asset-category column, and make is the closest thing
        // to a type grouping the data actually supports.
        $assetType = trim((string) ($filters['asset_type'] ?? ''));
        if ($assetType !== '') {
            $sql .= " AND a.make = :asset_type";
            $params['asset_type'] = $assetType;
        }

        $minPrice = $filters['min_price'] ?? '';
        if ($minPrice !== '' && is_numeric($minPrice)) {
            $sql .= " AND c.nav_per_share >= :min_price";
            $params['min_price'] = $minPrice;
        }

        $maxPrice = $filters['max_price'] ?? '';
        if ($maxPrice !== '' && is_numeric($maxPrice)) {
            $sql .= " AND c.nav_per_share <= :max_price";
            $params['max_price'] = $maxPrice;
        }

        // Whitelisted, never built from user input directly - avoids injecting
        // into ORDER BY while still letting the sort come from the query string.
        $sort = (string) ($filters['sort'] ?? '');
        $sql .= " ORDER BY " . match ($sort) {
            'price_asc' => 'c.nav_per_share ASC',
            'price_desc' => 'c.nav_per_share DESC',
            default => 'c.created_at DESC',
        };

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['shares_available'] = self::sharesAvailable((int) $row['id'], (int) $row['shares_issued']);
        }
        unset($row);

        // "Most available" depends on the derived shares_available figure above,
        // so it's sorted here in PHP rather than in SQL.
        if ($sort === 'available_desc') {
            usort($rows, fn ($a, $b) => $b['shares_available'] <=> $a['shares_available']);
        }

        return $rows;
    }

    // Distinct category (activity type), location and asset type (make) values across
    // active listings, used to populate the discovery page's filter dropdowns/pills.
    // None of these are a fixed enum in this platform - they're whatever admins have
    // typed for each SPV's activity/asset - so the filter options are derived from
    // real data, not hardcoded.
    public static function filterFacets(): array
    {
        $db = Database::connection();

        $latestActivityJoin = "FROM companies c
             JOIN commercial_activities ca ON ca.company_id = c.id
                AND ca.id = (SELECT MAX(id) FROM commercial_activities WHERE company_id = c.id)
             WHERE c.status = 'active'";

        $categories = $db->query(
            "SELECT DISTINCT ca.activity_type AS value {$latestActivityJoin}
                AND ca.activity_type IS NOT NULL AND ca.activity_type <> ''
             ORDER BY ca.activity_type"
        )->fetchAll(\PDO::FETCH_COLUMN);

        $locations = $db->query(
            "SELECT DISTINCT ca.location AS value {$latestActivityJoin}
                AND ca.location IS NOT NULL AND ca.location <> ''
             ORDER BY ca.location"
        )->fetchAll(\PDO::FETCH_COLUMN);

        $assetTypes = $db->query(
            "SELECT DISTINCT a.make AS value
             FROM companies c
             JOIN assets a ON a.company_id = c.id
             WHERE c.status = 'active' AND a.make IS NOT NULL AND a.make <> ''
             ORDER BY a.make"
        )->fetchAll(\PDO::FETCH_COLUMN);

        return ['categories' => $categories, 'locations' => $locations, 'assetTypes' => $assetTypes];
    }

    public static function activeListingCount(): int
    {
        $db = Database::connection();
        return (int) $db->query("SELECT COUNT(*) FROM companies WHERE status = 'active'")->fetchColumn();
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
