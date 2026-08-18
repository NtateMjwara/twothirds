<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class AssetImage extends Model
{
    protected static string $table = 'asset_images';

    /** Ordered for display: the primary image first, then whatever order an admin set. */
    public static function forCompany(int $companyId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM asset_images
             WHERE company_id = ?
             ORDER BY is_primary DESC, sort_order, id"
        );
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }

    /**
     * The one image used on cards and as the page hero. Falls back to the
     * earliest uploaded image, so a company always has a cover as soon as
     * anything is uploaded, whether or not an admin has chosen one.
     */
    public static function primaryForCompany(int $companyId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM asset_images
             WHERE company_id = ?
             ORDER BY is_primary DESC, sort_order, id
             LIMIT 1"
        );
        $stmt->execute([$companyId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Cover images for a set of companies in one query, keyed by company id.
     * The discovery grid needs a cover for a page of listings at once; one
     * query per card would be twelve round trips.
     */
    public static function primariesFor(array $companyIds): array
    {
        if (!$companyIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT ai.* FROM asset_images ai
             WHERE ai.company_id IN ({$placeholders})
             ORDER BY ai.company_id, ai.is_primary DESC, ai.sort_order, ai.id"
        );
        $stmt->execute(array_map('intval', $companyIds));

        $covers = [];
        foreach ($stmt->fetchAll() as $row) {
            // First row per company wins; the ORDER BY has already decided which.
            $covers[(int) $row['company_id']] ??= $row;
        }
        return $covers;
    }

    /** Exactly one primary per company, enforced here rather than by a constraint. */
    public static function makePrimary(int $companyId, int $imageId): void
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE asset_images SET is_primary = 0 WHERE company_id = ?")
               ->execute([$companyId]);
            $db->prepare("UPDATE asset_images SET is_primary = 1 WHERE id = ? AND company_id = ?")
               ->execute([$imageId, $companyId]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM asset_images WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare("DELETE FROM asset_images WHERE id = ?")->execute([$id]);
    }

    public static function nextSortOrder(int $companyId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(MAX(sort_order), 0) + 1 FROM asset_images WHERE company_id = ?"
        );
        $stmt->execute([$companyId]);
        return (int) $stmt->fetchColumn();
    }
}
