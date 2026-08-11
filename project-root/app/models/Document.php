<?php
namespace app\models;

use app\core\Model;

class Document extends Model
{
    protected static string $table = 'documents';

    public static function forCompany(int $companyId): array
    {
        return static::where('company_id', $companyId);
    }
}
