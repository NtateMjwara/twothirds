<?php
namespace app\services;

use app\core\Database;
use app\models\Commitment;
use app\models\Company;

class CommitmentService
{
    public static function create(int $userId, int $companyId, int $sharesRequested): array
    {
        $company = Company::find($companyId);
        if (!$company) {
            throw new \RuntimeException('Company not found.');
        }

        $available = CompanyService::sharesAvailable($companyId, (int) $company['shares_issued']);
        if ($sharesRequested < 1 || $sharesRequested > $available) {
            throw new \InvalidArgumentException('Requested shares exceed what is currently available.');
        }

        $id = Commitment::create([
            'user_id'          => $userId,
            'company_id'       => $companyId,
            'shares_requested' => $sharesRequested,
            'status'           => 'pending',
            'expires_at'       => date('Y-m-d H:i:s', strtotime('+14 days')),
        ]);

        $reference = $company['reference'] . '-C-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
        Database::connection()->prepare("UPDATE commitments SET reference = ? WHERE id = ?")->execute([$reference, $id]);

        return ['id' => $id, 'reference' => $reference];
    }
}
