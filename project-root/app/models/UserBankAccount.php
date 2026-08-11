<?php
namespace app\models;

use app\core\Model;

class UserBankAccount extends Model
{
    protected static string $table = 'user_bank_accounts';

    public static function primaryForUser(int $userId): ?array
    {
        $rows = static::where('user_id', $userId);
        foreach ($rows as $row) {
            if ((int) $row['is_primary'] === 1) {
                return $row;
            }
        }
        return $rows[0] ?? null;
    }
}
