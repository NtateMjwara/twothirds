<?php
namespace app\models;

use app\core\Model;

class UserProfile extends Model
{
    protected static string $table = 'user_profiles';

    public static function forUser(int $userId): ?array
    {
        $rows = static::where('user_id', $userId);
        return $rows[0] ?? null;
    }
}
