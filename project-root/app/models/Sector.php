<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class Sector extends Model
{
    protected static string $table = 'sectors';

    public static function allActive(): array
    {
        return Database::connection()->query(
            "SELECT * FROM sectors WHERE status = 'active' ORDER BY sort_order, name"
        )->fetchAll();
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM sectors WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }
}
