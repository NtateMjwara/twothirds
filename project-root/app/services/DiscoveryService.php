<?php
namespace app\services;

use app\core\Database;
use app\models\AssetImage;

/**
 * Everything the /discover page needs to read.
 *
 * Kept separate from CompanyService on purpose: CompanyService owns writes and
 * the share-availability rules, this owns the read model for browsing. The one
 * rule they share - available = issued - held - pending - is expressed here in
 * SQL rather than PHP so that sorting, availability filters and LIMIT/OFFSET
 * pagination all happen in one round trip instead of loading every active
 * listing into memory to slice it.
 */
class DiscoveryService
{
    public const PER_PAGE = 12;

    // Curated entry points. Each is a saved filter, not a database column, so
    // adding a theme costs one array entry and no migration.
    public static function themes(): array
    {
        return [
            'newly-listed' => [
                'label' => 'Newly listed',
                'blurb' => 'Opened in the last 30 days',
                'icon'  => 'ti-sparkles',
            ],
            'nearly-funded' => [
                'label' => 'Closing soon',
                'blurb' => 'More than 75% subscribed',
                'icon'  => 'ti-flame',
            ],
            'just-opened' => [
                'label' => 'Room to fill',
                'blurb' => 'Less than a quarter taken up',
                'icon'  => 'ti-door-enter',
            ],
            'entry-level' => [
                'label' => 'Under R100 a share',
                'blurb' => 'Smallest ticket to get started',
                'icon'  => 'ti-coin',
            ],
            'high-utilisation' => [
                'label' => 'Hardest working',
                'blurb' => 'Utilisation at 80% or better',
                'icon'  => 'ti-activity-heartbeat',
            ],
            'earning-now' => [
                'label' => 'Already earning',
                'blurb' => 'Has filed a trading period',
                'icon'  => 'ti-report-money',
            ],
        ];
    }

    public static function sortOptions(): array
    {
        return [
            'newest'          => 'Newest first',
            'funded_desc'     => 'Closest to fully subscribed',
            'available_desc'  => 'Most shares available',
            'price_asc'       => 'NAV: low to high',
            'price_desc'      => 'NAV: high to low',
            'utilisation_desc'=> 'Highest utilisation',
            'name_asc'        => 'Name: A to Z',
        ];
    }

    // The filter keys the page understands. Anything outside this list is
    // ignored, which is what keeps query_with() links from carrying junk.
    public static function emptyFilters(): array
    {
        return [
            'q' => '', 'sector' => '', 'activity' => '', 'asset_class' => '',
            'location' => '', 'min_price' => '', 'max_price' => '',
            'availability' => '', 'theme' => '', 'sort' => '',
        ];
    }

    // ============================================================
    // Shared SQL fragments
    // ============================================================

    // One row per company: its most recently captured commercial activity.
    private static function latestActivity(): string
    {
        return "(SELECT ca.company_id, ca.activity_type_id, ca.activity_type, ca.location,
                        ca.operator, ca.utilisation_rate, ca.start_date
                 FROM commercial_activities ca
                 WHERE ca.id = (SELECT MAX(x.id) FROM commercial_activities x WHERE x.company_id = ca.company_id))";
    }

    // One row per company: its first-registered asset. Companies are
    // single-asset by design, but the join must not fan out if that ever changes.
    private static function firstAsset(): string
    {
        return "(SELECT a.company_id, a.asset_class_id, a.make, a.model, a.year, a.mileage,
                        a.current_valuation, a.asset_status
                 FROM assets a
                 WHERE a.id = (SELECT MIN(x.id) FROM assets x WHERE x.company_id = a.company_id))";
    }

    private static function fromClause(): string
    {
        return "FROM companies c
                LEFT JOIN " . self::firstAsset() . " fa ON fa.company_id = c.id
                LEFT JOIN asset_classes acl ON acl.id = fa.asset_class_id
                LEFT JOIN " . self::latestActivity() . " la ON la.company_id = c.id
                LEFT JOIN activity_types att ON att.id = la.activity_type_id
                LEFT JOIN sectors sec ON sec.id = att.sector_id";
    }

    // ============================================================
    // Listings
    // ============================================================

    /**
     * @return array{rows: array, total: int, page: int, pages: int, per_page: int}
     */
    public static function listings(array $filters, int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $db = Database::connection();
        [$where, $having, $params] = self::buildClauses($filters);

        $inner = "SELECT c.id, c.reference, c.name, c.shares_issued, c.nav_per_share, c.created_at,
                         fa.make, fa.model, fa.year, fa.current_valuation,
                         acl.name AS asset_class_name, acl.slug AS asset_class_slug,
                         COALESCE(acl.icon, 'ti-car') AS asset_icon,
                         la.activity_type AS activity_label, la.location, la.operator, la.utilisation_rate,
                         att.name AS activity_name, att.slug AS activity_slug,
                         sec.name AS sector_name, sec.slug AS sector_slug, sec.icon AS sector_icon,
                         COALESCE((SELECT SUM(sh.shares) FROM shareholdings sh
                                   WHERE sh.company_id = c.id), 0) AS shares_held,
                         COALESCE((SELECT SUM(cm.shares_requested) FROM commitments cm
                                   WHERE cm.company_id = c.id AND cm.status = 'pending'), 0) AS shares_pending,
                         (SELECT COUNT(*) FROM financial_periods fp WHERE fp.company_id = c.id) AS period_count
                  " . self::fromClause() . "
                  WHERE {$where}";

        $outer = "SELECT t.*,
                         GREATEST(t.shares_issued - t.shares_held - t.shares_pending, 0) AS shares_available,
                         CASE WHEN t.shares_issued > 0
                              THEN LEAST(100, ROUND(((t.shares_held + t.shares_pending) / t.shares_issued) * 100))
                              ELSE 0 END AS funded_pct
                  FROM ({$inner}) t
                  HAVING {$having}";

        // Cast, not bind: LIMIT/OFFSET can't take bound parameters on native
        // (non-emulated) prepares, and an int cast is injection-proof.
        $page = max(1, $page);
        $perPage = max(1, min(60, $perPage));
        $offset = ($page - 1) * $perPage;

        $countStmt = $db->prepare("SELECT COUNT(*) FROM ({$outer}) x");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
            $offset = ($page - 1) * $perPage;
        }

        $stmt = $db->prepare("{$outer} ORDER BY " . self::orderBy($filters['sort'] ?? '') . " LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);

        return [
            'rows'     => self::attachCovers($stmt->fetchAll()),
            'total'    => $total,
            'page'     => $page,
            'pages'    => $pages,
            'per_page' => $perPage,
        ];
    }

    /**
     * Adds each listing's cover photograph.
     *
     * One query for the whole page rather than one per card - a page of twelve
     * cards was otherwise twelve extra round trips for what is decoration on a
     * grid that is already paginated.
     */
    private static function attachCovers(array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }

        $covers = AssetImage::primariesFor(array_column($rows, 'id'));

        foreach ($rows as &$row) {
            $cover = $covers[(int) $row['id']] ?? null;
            $row['cover_path'] = $cover['file_path'] ?? null;
            $row['cover_caption'] = $cover['caption'] ?? null;
        }
        unset($row);

        return $rows;
    }

    // Whitelisted. Never interpolate the raw sort value into SQL.
    private static function orderBy(string $sort): string
    {
        return match ($sort) {
            'price_asc'        => 't.nav_per_share ASC, t.name ASC',
            'price_desc'       => 't.nav_per_share DESC, t.name ASC',
            'available_desc'   => 'shares_available DESC, t.created_at DESC',
            'funded_desc'      => 'funded_pct DESC, t.created_at DESC',
            'utilisation_desc' => 't.utilisation_rate DESC, t.created_at DESC',
            'name_asc'         => 't.name ASC',
            default            => 't.created_at DESC, t.id DESC',
        };
    }

    /**
     * Splits the filter set into an inner WHERE (things the database can decide
     * from a column) and an outer HAVING (things derived from availability,
     * which only exists once the subquery totals are computed).
     *
     * @return array{0: string, 1: string, 2: array}
     */
    private static function buildClauses(array $filters): array
    {
        $where = ["c.status = 'active'"];
        $having = ['1 = 1'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            // Distinct placeholders for one value: native prepares reject the
            // same named parameter appearing twice in a statement.
            $where[] = "(c.name LIKE :q1 OR c.reference LIKE :q2 OR fa.make LIKE :q3
                         OR fa.model LIKE :q4 OR att.name LIKE :q5 OR sec.name LIKE :q6
                         OR la.location LIKE :q7 OR acl.name LIKE :q8)";
            $like = "%{$q}%";
            foreach (range(1, 8) as $i) {
                $params["q{$i}"] = $like;
            }
        }

        $map = [
            'sector'      => 'sec.slug',
            'activity'    => 'att.slug',
            'asset_class' => 'acl.slug',
            'location'    => 'la.location',
        ];
        foreach ($map as $key => $column) {
            $value = trim((string) ($filters[$key] ?? ''));
            if ($value !== '') {
                $where[] = "{$column} = :{$key}";
                $params[$key] = $value;
            }
        }

        foreach (['min_price' => '>=', 'max_price' => '<='] as $key => $operator) {
            $value = $filters[$key] ?? '';
            if ($value !== '' && is_numeric($value)) {
                $where[] = "c.nav_per_share {$operator} :{$key}";
                $params[$key] = $value;
            }
        }

        switch ((string) ($filters['availability'] ?? '')) {
            case 'open':
                $having[] = 'shares_available > 0';
                break;
            case 'subscribed':
                $having[] = 'shares_available = 0';
                break;
        }

        switch ((string) ($filters['theme'] ?? '')) {
            case 'newly-listed':
                $where[] = 'c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
            case 'nearly-funded':
                $having[] = 'funded_pct >= 75 AND shares_available > 0';
                break;
            case 'just-opened':
                $having[] = 'funded_pct < 25';
                break;
            case 'entry-level':
                $where[] = 'c.nav_per_share <= 100';
                break;
            case 'high-utilisation':
                $where[] = 'la.utilisation_rate >= 80';
                break;
            case 'earning-now':
                $where[] = 'EXISTS (SELECT 1 FROM financial_periods fp WHERE fp.company_id = c.id)';
                break;
        }

        return [implode(' AND ', $where), implode(' AND ', $having), $params];
    }

    // ============================================================
    // Facets
    // ============================================================

    /**
     * Counts are taken across every active listing, not across the currently
     * filtered set. That's deliberate: the sector row and category tiles are
     * navigation, and a count that collapses to zero the moment you pick a
     * filter makes them useless for moving sideways.
     */
    public static function sectorFacets(): array
    {
        $la = self::latestActivity();

        return Database::connection()->query(
            "SELECT s.id, s.slug, s.name, s.tagline, s.icon,
                    (SELECT COUNT(*)
                     FROM companies c
                     JOIN {$la} la ON la.company_id = c.id
                     JOIN activity_types att ON att.id = la.activity_type_id
                     WHERE c.status = 'active' AND att.sector_id = s.id) AS listing_count
             FROM sectors s
             WHERE s.status = 'active'
             ORDER BY s.sort_order, s.name"
        )->fetchAll();
    }

    // Category tiles. Narrowed to one sector when the visitor has picked one,
    // otherwise the busiest activities across the whole platform.
    public static function activityFacets(string $sectorSlug = '', int $limit = 18): array
    {
        $db = Database::connection();
        $la = self::latestActivity();

        $sql = "SELECT att.id, att.slug, att.name, att.description,
                       COALESCE(att.icon, s.icon) AS icon,
                       s.slug AS sector_slug, s.name AS sector_name, att.sort_order,
                       COUNT(c.id) AS listing_count
                FROM activity_types att
                JOIN sectors s ON s.id = att.sector_id
                LEFT JOIN {$la} la ON la.activity_type_id = att.id
                LEFT JOIN companies c ON c.id = la.company_id AND c.status = 'active'
                WHERE att.status = 'active' AND s.status = 'active'";

        $params = [];
        if ($sectorSlug !== '') {
            $sql .= " AND s.slug = :sector";
            $params['sector'] = $sectorSlug;
        }

        $sql .= " GROUP BY att.id, att.slug, att.name, att.description, att.icon,
                           s.icon, s.slug, s.name, att.sort_order
                  ORDER BY listing_count DESC, att.sort_order, att.name
                  LIMIT " . (int) $limit;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function assetClassFacets(): array
    {
        $fa = self::firstAsset();

        $rows = Database::connection()->query(
            "SELECT acl.slug, acl.name, acl.family, COUNT(c.id) AS listing_count
             FROM asset_classes acl
             LEFT JOIN {$fa} fa ON fa.asset_class_id = acl.id
             LEFT JOIN companies c ON c.id = fa.company_id AND c.status = 'active'
             WHERE acl.status = 'active'
             GROUP BY acl.slug, acl.name, acl.family, acl.sort_order
             HAVING listing_count > 0
             ORDER BY acl.sort_order, acl.name"
        )->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['family']][] = $row;
        }
        return $grouped;
    }

    public static function locationFacets(): array
    {
        $la = self::latestActivity();

        return Database::connection()->query(
            "SELECT la.location AS name, COUNT(*) AS listing_count
             FROM companies c
             JOIN {$la} la ON la.company_id = c.id
             WHERE c.status = 'active' AND la.location IS NOT NULL AND la.location <> ''
             GROUP BY la.location
             ORDER BY listing_count DESC, la.location"
        )->fetchAll();
    }

    public static function activeListingCount(): int
    {
        return (int) Database::connection()
            ->query("SELECT COUNT(*) FROM companies WHERE status = 'active'")
            ->fetchColumn();
    }

    // ============================================================
    // Watchlist strip
    // ============================================================

    /**
     * The saved-listings row at the top of the page. Each entry carries enough
     * to draw a snapshot without a second request: current NAV, how subscribed
     * it is, and a short trading history for the sparkline.
     */
    public static function watchlistSnapshot(int $userId, int $limit = 8): array
    {
        $db = Database::connection();

        $sql = "SELECT t.*,
                       GREATEST(t.shares_issued - t.shares_held - t.shares_pending, 0) AS shares_available,
                       CASE WHEN t.shares_issued > 0
                            THEN LEAST(100, ROUND(((t.shares_held + t.shares_pending) / t.shares_issued) * 100))
                            ELSE 0 END AS funded_pct
                FROM (
                    SELECT c.id, c.reference, c.name, c.nav_per_share, c.shares_issued,
                           w.created_at AS watched_at,
                           la.location, la.utilisation_rate,
                           att.name AS activity_name,
                           sec.name AS sector_name, sec.slug AS sector_slug,
                           COALESCE(sec.icon, 'ti-car') AS sector_icon,
                           COALESCE((SELECT SUM(sh.shares) FROM shareholdings sh
                                     WHERE sh.company_id = c.id), 0) AS shares_held,
                           COALESCE((SELECT SUM(cm.shares_requested) FROM commitments cm
                                     WHERE cm.company_id = c.id AND cm.status = 'pending'), 0) AS shares_pending
                    FROM watchlist w
                    JOIN companies c ON c.id = w.company_id
                    LEFT JOIN " . self::latestActivity() . " la ON la.company_id = c.id
                    LEFT JOIN activity_types att ON att.id = la.activity_type_id
                    LEFT JOIN sectors sec ON sec.id = att.sector_id
                    WHERE w.user_id = :user_id
                ) t
                ORDER BY t.watched_at DESC
                LIMIT " . (int) max(1, $limit);

        $stmt = $db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        return self::attachTrends($rows);
    }

    // Shown to signed-out visitors in place of a watchlist, so the strip isn't
    // an empty box on a first visit.
    public static function mostWatched(int $limit = 8): array
    {
        $sql = "SELECT t.*,
                       GREATEST(t.shares_issued - t.shares_held - t.shares_pending, 0) AS shares_available,
                       CASE WHEN t.shares_issued > 0
                            THEN LEAST(100, ROUND(((t.shares_held + t.shares_pending) / t.shares_issued) * 100))
                            ELSE 0 END AS funded_pct
                FROM (
                    SELECT c.id, c.reference, c.name, c.nav_per_share, c.shares_issued,
                           la.location, la.utilisation_rate,
                           att.name AS activity_name,
                           sec.name AS sector_name, sec.slug AS sector_slug,
                           COALESCE(sec.icon, 'ti-car') AS sector_icon,
                           (SELECT COUNT(*) FROM watchlist w WHERE w.company_id = c.id) AS watcher_count,
                           COALESCE((SELECT SUM(sh.shares) FROM shareholdings sh
                                     WHERE sh.company_id = c.id), 0) AS shares_held,
                           COALESCE((SELECT SUM(cm.shares_requested) FROM commitments cm
                                     WHERE cm.company_id = c.id AND cm.status = 'pending'), 0) AS shares_pending
                    FROM companies c
                    LEFT JOIN " . self::latestActivity() . " la ON la.company_id = c.id
                    LEFT JOIN activity_types att ON att.id = la.activity_type_id
                    LEFT JOIN sectors sec ON sec.id = att.sector_id
                    WHERE c.status = 'active'
                ) t
                ORDER BY t.watcher_count DESC, t.id DESC
                LIMIT " . (int) max(1, $limit);

        return self::attachTrends(Database::connection()->query($sql)->fetchAll());
    }

    /**
     * Adds a `spark` array (net operating income by period, oldest first) and a
     * `spark_change` percentage to each row. Companies with no filed periods get
     * an empty spark and a null change - the card draws a flat line and says so
     * rather than inventing a trend.
     */
    private static function attachTrends(array $rows): array
    {
        if (!$rows) {
            return [];
        }

        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = Database::connection()->prepare(
            "SELECT company_id, net_operating_income
             FROM financial_periods
             WHERE company_id IN ({$placeholders})
             ORDER BY company_id, period_start ASC"
        );
        $stmt->execute($ids);

        $series = [];
        foreach ($stmt->fetchAll() as $period) {
            $series[(int) $period['company_id']][] = (float) $period['net_operating_income'];
        }

        foreach ($rows as &$row) {
            $points = array_slice($series[(int) $row['id']] ?? [], -8);
            $row['spark'] = $points;
            $row['spark_change'] = null;

            $count = count($points);
            if ($count >= 2) {
                $previous = $points[$count - 2];
                if (abs($previous) > 0.0001) {
                    $row['spark_change'] = round((($points[$count - 1] - $previous) / abs($previous)) * 100, 1);
                }
            }
        }
        unset($row);

        return $rows;
    }
}
