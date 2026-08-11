<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class Company extends Model
{
    protected static string $table = 'companies';

    public static function findByReference(string $reference): ?array
    {
        $rows = static::where('reference', $reference);
        return $rows[0] ?? null;
    }

    // Inserts the row, then stamps a human-readable reference (e.g. SPV-00842)
    // derived from the new auto-increment id, and saves it back.
    public static function createWithReference(array $data): array
    {
        $id = static::create($data);
        $reference = 'SPV-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);

        $stmt = Database::connection()->prepare("UPDATE companies SET reference = ? WHERE id = ?");
        $stmt->execute([$reference, $id]);

        return ['id' => $id, 'reference' => $reference];
    }
}
