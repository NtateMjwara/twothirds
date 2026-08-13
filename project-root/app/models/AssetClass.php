<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class AssetClass extends Model
{
    protected static string $table = 'asset_classes';

    public static function allActive(): array
    {
        return Database::connection()->query(
            "SELECT * FROM asset_classes WHERE status = 'active' ORDER BY sort_order, name"
        )->fetchAll();
    }

    // Nested under 'family' (Passenger, Trucks, Plant, ...) for <optgroup> lists.
    public static function groupedByFamily(): array
    {
        $grouped = [];
        foreach (self::allActive() as $row) {
            $grouped[$row['family']][] = $row;
        }
        return $grouped;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM asset_classes WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }
}
