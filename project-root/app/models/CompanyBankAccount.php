<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

/**
 * An SPV's own bank account.
 *
 * Never joined by anything public. It is read in exactly two places: the admin
 * screen that maintains it, and the invoice that tells an investor where to pay.
 */
class CompanyBankAccount extends Model
{
    protected static string $table = 'company_bank_accounts';

    public static function forCompany(int $companyId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM company_bank_accounts WHERE company_id = ? LIMIT 1"
        );
        $stmt->execute([$companyId]);
        return $stmt->fetch() ?: null;
    }

    /** Insert or update, so callers don't have to know which. */
    public static function save(int $companyId, array $data): void
    {
        $existing = self::forCompany($companyId);

        if ($existing) {
            self::update((int) $existing['id'], $data);
            return;
        }

        self::create($data + ['company_id' => $companyId]);
    }

    /**
     * True when there is enough here to put on an invoice.
     *
     * An SPV with no banking details can still be created and listed - the
     * account is usually opened after incorporation - but it must not be able
     * to take a commitment, because the investor would be told to pay and given
     * nowhere to pay it.
     */
    public static function isComplete(?array $account): bool
    {
        if (!$account) {
            return false;
        }

        foreach (['account_holder_name', 'bank_name', 'account_number', 'branch_code'] as $field) {
            if (trim((string) ($account[$field] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }
}
