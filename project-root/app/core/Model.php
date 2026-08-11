<?php
namespace app\core;

abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function where(string $column, $value): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM " . static::$table . " WHERE {$column} = ?"
        );
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        return Database::connection()->query("SELECT * FROM " . static::$table)->fetchAll();
    }

    public static function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO " . static::$table . " (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(array_values($data));
        return (int) Database::connection()->lastInsertId();
    }

    // Ledger-style tables (e.g. Shareholding) should override this to throw -
    // those rows are append-only and must never be edited in place.
    public static function update(int $id, array $data): bool
    {
        $assignments = implode(',', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE " . static::$table . " SET {$assignments} WHERE " . static::$primaryKey . " = ?";
        $stmt = Database::connection()->prepare($sql);
        return $stmt->execute([...array_values($data), $id]);
    }
}
