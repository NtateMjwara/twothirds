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

    /**
     * The saved list, in the same shape the discovery grid produces.
     *
     * Deliberately the same row shape rather than a thinner one: the watchlist
     * and the results grid show the same thing, so they should use the same
     * card. The old query returned three columns and the page grew its own
     * cut-down card that then drifted out of step with the real one.
     */
    public static function forUser(int $userId): array
    {
        $latestActivity = "(SELECT ca.company_id, ca.activity_type_id, ca.activity_type,
                                   ca.location, ca.utilisation_rate
                            FROM commercial_activities ca
                            WHERE ca.id = (SELECT MAX(x.id) FROM commercial_activities x
                                           WHERE x.company_id = ca.company_id))";

        $firstAsset = "(SELECT a.company_id, a.asset_class_id, a.make, a.model, a.year
                        FROM assets a
                        WHERE a.id = (SELECT MIN(x.id) FROM assets x WHERE x.company_id = a.company_id))";

        $sql = "SELECT t.*,
                       GREATEST(t.shares_issued - t.shares_held - t.shares_pending, 0) AS shares_available,
                       CASE WHEN t.shares_issued > 0
                            THEN LEAST(100, ROUND(((t.shares_held + t.shares_pending) / t.shares_issued) * 100))
                            ELSE 0 END AS funded_pct
                FROM (
                    SELECT c.id, c.reference, c.name, c.status, c.shares_issued, c.nav_per_share,
                           c.created_at, w.created_at AS saved_at,
                           fa.make, fa.model, fa.year,
                           acl.name AS asset_class_name, COALESCE(acl.icon, 'ti-car') AS asset_icon,
                           la.activity_type AS activity_label, la.location, la.utilisation_rate,
                           att.name AS activity_name,
                           sec.name AS sector_name, sec.slug AS sector_slug,
                           COALESCE(sec.icon, 'ti-briefcase') AS sector_icon,
                           COALESCE((SELECT SUM(sh.shares) FROM shareholdings sh
                                     WHERE sh.company_id = c.id), 0) AS shares_held,
                           COALESCE((SELECT SUM(cm.shares_requested) FROM commitments cm
                                     WHERE cm.company_id = c.id AND cm.status = 'pending'), 0) AS shares_pending,
                           (SELECT COUNT(*) FROM financial_periods fp WHERE fp.company_id = c.id) AS period_count
                    FROM watchlist w
                    JOIN companies c ON c.id = w.company_id
                    LEFT JOIN {$firstAsset} fa ON fa.company_id = c.id
                    LEFT JOIN asset_classes acl ON acl.id = fa.asset_class_id
                    LEFT JOIN {$latestActivity} la ON la.company_id = c.id
                    LEFT JOIN activity_types att ON att.id = la.activity_type_id
                    LEFT JOIN sectors sec ON sec.id = att.sector_id
                    WHERE w.user_id = :user_id
                ) t
                ORDER BY t.saved_at DESC";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        // Cover images for the whole page in one query rather than one per card.
        $covers = AssetImage::primariesFor(array_column($rows, 'id'));

        foreach ($rows as &$row) {
            $cover = $covers[(int) $row['id']] ?? null;
            $row['cover_path'] = $cover['file_path'] ?? null;
            $row['cover_caption'] = $cover['caption'] ?? null;
        }
        unset($row);

        return $rows;
    }

    public static function remove(int $userId, int $companyId): void
    {
        Database::connection()->prepare(
            "DELETE FROM watchlist WHERE user_id = ? AND company_id = ?"
        )->execute([$userId, $companyId]);
    }
}
