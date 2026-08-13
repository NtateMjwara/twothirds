<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class ActivityType extends Model
{
    protected static string $table = 'activity_types';

    public static function allActive(): array
    {
        return Database::connection()->query(
            "SELECT att.*, s.slug AS sector_slug, s.name AS sector_name, s.icon AS sector_icon
             FROM activity_types att
             JOIN sectors s ON s.id = att.sector_id
             WHERE att.status = 'active' AND s.status = 'active'
             ORDER BY s.sort_order, att.sort_order, att.name"
        )->fetchAll();
    }

    // Same rows as allActive(), nested under their sector name - the shape the
    // admin form's <optgroup> list needs.
    public static function groupedBySector(): array
    {
        $grouped = [];
        foreach (self::allActive() as $row) {
            $grouped[$row['sector_name']][] = $row;
        }
        return $grouped;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT att.*, s.slug AS sector_slug, s.name AS sector_name
             FROM activity_types att
             JOIN sectors s ON s.id = att.sector_id
             WHERE att.slug = ? LIMIT 1"
        );
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    // The label written into commercial_activities.activity_type so the legacy
    // varchar column stays truthful for anything still reading it.
    public static function labelFor(?int $id): string
    {
        if (!$id) {
            return '';
        }
        $stmt = Database::connection()->prepare("SELECT name FROM activity_types WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return (string) ($stmt->fetchColumn() ?: '');
    }
}
