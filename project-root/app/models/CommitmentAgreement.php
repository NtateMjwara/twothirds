<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class CommitmentAgreement extends Model
{
    protected static string $table = 'commitment_agreements';

    public static function forCommitment(int $commitmentId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM commitment_agreements WHERE commitment_id = ? ORDER BY id"
        );
        $stmt->execute([$commitmentId]);
        return $stmt->fetchAll();
    }
}
