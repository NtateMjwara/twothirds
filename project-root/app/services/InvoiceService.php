<?php
namespace app\services;

use app\core\Crypto;
use app\core\Database;
use app\models\CompanyBankAccount;
use app\models\CommitmentAgreement;
use app\models\LegalDocument;

/**
 * Invoicing for a commitment.
 *
 * Everything here is written once, at the moment of commitment, and never
 * recalculated. An invoice is a statement of what was owed on a given day: if
 * the fee rate changes next month, or the asset is revalued next week, an
 * invoice issued today has to still say what it said today.
 *
 * That is why the NAV, the fee rate and all three amounts are columns on
 * `commitments` rather than being derived on read.
 */
class InvoiceService
{
    /**
     * Mirrors the fees page. Stored per commitment, so changing this only
     * affects invoices issued afterwards.
     */
    public const TRANSACTION_FEE_RATE = 0.06;

    /**
     * Records what the investor agreed to, prices the commitment, and queues
     * the invoice email.
     *
     * @param array $documents legal_documents rows that were accepted
     * @return array the commitment as invoiced
     */
    public static function issue(array $commitment, array $company, array $user, array $documents): array
    {
        $db = Database::connection();

        $shares = (int) $commitment['shares_requested'];
        $nav = (float) $company['nav_per_share'];

        $shareAmount = round($shares * $nav, 2);
        $feeAmount = round($shareAmount * self::TRANSACTION_FEE_RATE, 2);
        $totalDue = round($shareAmount + $feeAmount, 2);

        $bank = CompanyBankAccount::forCompany((int) $company['id']);
        $paymentReference = self::paymentReference($commitment, $bank);
        $invoiceNumber = self::nextInvoiceNumber();

        $db->beginTransaction();

        try {
            $db->prepare(
                "UPDATE commitments
                 SET invoice_number = ?, nav_at_commitment = ?, transaction_fee_rate = ?,
                     share_amount = ?, fee_amount = ?, total_due = ?, payment_reference = ?
                 WHERE id = ?"
            )->execute([
                $invoiceNumber, $nav, self::TRANSACTION_FEE_RATE,
                $shareAmount, $feeAmount, $totalDue, $paymentReference,
                (int) $commitment['id'],
            ]);

            // Consent is recorded in the same transaction as the pricing. An
            // invoice with no matching agreement record, or the reverse, would
            // be worse than neither.
            $insert = $db->prepare(
                "INSERT INTO commitment_agreements
                    (commitment_id, legal_document_id, doc_key, doc_version, doc_title, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            foreach ($documents as $doc) {
                $insert->execute([
                    (int) $commitment['id'],
                    (int) $doc['id'],
                    $doc['doc_key'],
                    $doc['version'],
                    $doc['title'],
                    self::clientIp(),
                    self::truncate((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 255) ?: null,
                ]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $invoice = self::build($commitment, $company, $user, $bank, [
            'invoice_number'    => $invoiceNumber,
            'nav'               => $nav,
            'share_amount'      => $shareAmount,
            'fee_amount'        => $feeAmount,
            'total_due'         => $totalDue,
            'payment_reference' => $paymentReference,
        ]);

        // The commitment and the consent are committed. A failed email must not
        // be reported as a failed commitment - the investor's shares are
        // reserved either way, and an admin can resend.
        try {
            self::send($invoice, $user);

            $db->prepare("UPDATE commitments SET invoice_sent_at = NOW() WHERE id = ?")
               ->execute([(int) $commitment['id']]);
        } catch (\Throwable $e) {
            error_log("Invoice {$invoiceNumber} was created but the email failed: " . $e->getMessage());
        }

        return $invoice;
    }

    /**
     * Everything the invoice needs, in one array, with the bank account already
     * decrypted. Used by the email and by the confirmation page.
     */
    public static function build(array $commitment, array $company, array $user, ?array $bank, array $amounts): array
    {
        return [
            'invoice_number'    => $amounts['invoice_number'],
            'issued_at'         => date('Y-m-d H:i:s'),
            'due_at'            => $commitment['expires_at'] ?? null,
            'commitment'        => $commitment['reference'],
            'payment_reference' => $amounts['payment_reference'],
            'investor' => [
                'name'  => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['email'],
                'email' => $user['email'],
            ],
            'company' => [
                'name'      => $company['name'],
                'reference' => $company['reference'],
                'registration_number' => $company['registration_number'] ?? null,
            ],
            'shares'       => (int) $commitment['shares_requested'],
            'nav'          => $amounts['nav'],
            'share_amount' => $amounts['share_amount'],
            'fee_rate'     => self::TRANSACTION_FEE_RATE,
            'fee_amount'   => $amounts['fee_amount'],
            'total_due'    => $amounts['total_due'],
            'bank'         => self::readableBank($bank),
        ];
    }

    /** Decrypts the account number for display. Never stored decrypted. */
    private static function readableBank(?array $bank): ?array
    {
        if (!CompanyBankAccount::isComplete($bank)) {
            return null;
        }

        $number = Crypto::decrypt($bank['account_number']);

        if ($number === null) {
            // Wrong key or corrupt data. Returning null puts "contact us for
            // banking details" on the invoice, which is recoverable; printing
            // a mangled account number is not.
            error_log("Company bank account #{$bank['id']} could not be decrypted.");
            return null;
        }

        return [
            'account_holder' => $bank['account_holder_name'],
            'bank_name'      => $bank['bank_name'],
            'account_number' => $number,
            'branch_code'    => $bank['branch_code'],
            'account_type'   => $bank['account_type'],
            'swift_code'     => $bank['swift_code'] ?: null,
        ];
    }

    /** Most South African bank reference fields accept 20 characters. */
    private const REFERENCE_MAX = 20;

    /**
     * The reference the investor must quote on the deposit.
     *
     * The commitment reference already identifies the company and the
     * commitment uniquely, so it IS the reference. A per-company prefix can be
     * prepended for a finance team reading a bank statement.
     *
     * Length is the trap here. Bank reference fields are commonly capped at 20
     * characters, and naively truncating the whole string cuts the tail off the
     * commitment reference - turning a matchable payment into an unmatchable
     * one, which is the single worst outcome this function can produce. So the
     * commitment reference is protected: the prefix is trimmed to whatever
     * space is left, and dropped entirely if there isn't any.
     */
    private static function paymentReference(array $commitment, ?array $bank): string
    {
        $reference = strtoupper(trim((string) $commitment['reference']));
        $prefix = strtoupper(trim((string) ($bank['reference_prefix'] ?? '')));

        if ($prefix === '') {
            return substr($reference, 0, self::REFERENCE_MAX);
        }

        // Room for the prefix plus its separating hyphen.
        $room = self::REFERENCE_MAX - strlen($reference) - 1;

        if ($room < 1) {
            // The reference alone fills the field. The prefix is a convenience;
            // matching the payment is not.
            return substr($reference, 0, self::REFERENCE_MAX);
        }

        return substr($prefix, 0, $room) . '-' . $reference;
    }

    /**
     * Sequential within the year: INV-2026-000042.
     *
     * Derived from a count rather than an auto-increment because invoice
     * numbers should not have gaps where an unrelated row was deleted, and
     * should restart each year.
     */
    private static function nextInvoiceNumber(): string
    {
        $year = date('Y');

        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM commitments WHERE invoice_number LIKE ?"
        );
        $stmt->execute(["INV-{$year}-%"]);

        return sprintf('INV-%s-%06d', $year, ((int) $stmt->fetchColumn()) + 1);
    }


    /**
     * Truncate without needing mbstring.
     *
     * substr() alone would cut a multi-byte character in half and hand MySQL
     * invalid UTF-8, which fails the write rather than shortening the string.
     * The /u modifier makes `.` match a whole character, so this is safe on any
     * build of PHP - and this platform has already been bitten once by assuming
     * the extension is installed.
     */
    private static function truncate(string $value, int $max): string
    {
        return preg_match('/^.{0,' . $max . '}/us', $value, $m) ? $m[0] : substr($value, 0, $max);
    }

    private static function clientIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        // Only accept a well-formed address. A spoofed or malformed value in an
        // evidence record is worse than no value.
        return ($ip && filter_var($ip, FILTER_VALIDATE_IP)) ? $ip : null;
    }

    private static function send(array $invoice, array $user): void
    {
        ob_start();
        require __DIR__ . '/../views/emails/invoice.php';
        $html = ob_get_clean();

        NotificationService::notify(
            (int) $user['id'],
            'commitment_invoice',
            'Your commitment ' . $invoice['commitment'] . ' is reserved. Invoice '
                . $invoice['invoice_number'] . ' for R' . number_format($invoice['total_due'], 2)
                . ' is due by ' . date('j M Y', strtotime((string) $invoice['due_at'])) . '.',
            'Invoice ' . $invoice['invoice_number'] . ' — ' . $invoice['company']['name'],
            $html
        );
    }
}
