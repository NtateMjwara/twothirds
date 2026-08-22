<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class LegalDocument extends Model
{
    protected static string $table = 'legal_documents';

    /**
     * The documents an investor must see before committing to this company.
     *
     * A company-specific document overrides a platform-wide one with the same
     * key, so an SPV that has filed its own MOI shows that rather than the
     * generic placeholder - without the caller needing to know which exists.
     */
    public static function forCommitment(int $companyId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM legal_documents
             WHERE status = 'active'
               AND (company_id IS NULL OR company_id = ?)
               AND (effective_from IS NULL OR effective_from <= CURDATE())
             ORDER BY sort_order, id"
        );
        $stmt->execute([$companyId]);

        $byKey = [];
        foreach ($stmt->fetchAll() as $doc) {
            $key = $doc['doc_key'];
            if (!isset($byKey[$key]) || $doc['company_id'] !== null) {
                $byKey[$key] = $doc;
            }
        }

        return array_values($byKey);
    }

    public static function findActive(string $key, ?int $companyId = null): ?array
    {
        foreach (self::forCommitment((int) $companyId) as $doc) {
            if ($doc['doc_key'] === $key) {
                return $doc;
            }
        }
        return null;
    }
}
