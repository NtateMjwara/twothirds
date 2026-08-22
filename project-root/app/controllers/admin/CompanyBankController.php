<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\core\Crypto;
use app\core\Database;
use app\models\Company;
use app\models\CompanyBankAccount;
use app\services\ProfileOptions;

/**
 * The SPV's own bank account.
 *
 * Its own screen rather than a section of the edit form: it is the only place on
 * the platform where an account number is entered by someone other than its
 * owner, and every change to it needs its own audit entry.
 *
 * ---------------------------------------------------------------------------
 * On the defensiveness in here
 *
 * The first version called $this->flash() and $this->audit() directly. Both were
 * added to the core Controller and AdminController in an earlier package. If
 * either replacement wasn't applied - and Controller.php in particular is a core
 * file people are rightly cautious about overwriting - the GET renders fine and
 * the POST dies with "Call to undefined method", which reaches the browser as a
 * bare 500 containing nothing.
 *
 * That is a miserable thing to debug from the outside, so this controller now
 * assumes nothing: optional conveniences are used only if they exist, and the
 * whole save is wrapped so any failure is displayed on the page instead of
 * becoming a 500. Saving banking details is not the place to discover that a
 * base class is a version behind.
 * ---------------------------------------------------------------------------
 */
class CompanyBankController extends AdminController
{
    public function edit(string $reference): void
    {
        $this->requirePermission('company.manage');

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $this->renderForm($company);
    }

    public function update(string $reference): void
    {
        $this->requirePermission('company.manage');
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        try {
            $this->save($company);
        } catch (\Throwable $e) {
            // Anything at all - a missing method, a missing table, a bad key.
            // Shown rather than swallowed, because a 500 here tells whoever is
            // looking at it precisely nothing.
            error_log('Banking save failed for ' . $company['reference'] . ': ' . $e);

            $this->renderForm(
                $company,
                'The banking details could not be saved. Nothing was changed. '
                    . get_class($e) . ': ' . $e->getMessage()
                    . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')'
            );
        }
    }

    // ------------------------------------------------------------

    private function save(array $company): void
    {
        $existing = CompanyBankAccount::forCompany((int) $company['id']);

        $holder = trim($_POST['account_holder_name'] ?? '');
        $bank = trim($_POST['bank_name'] ?? '');
        $number = preg_replace('/\s+/', '', trim($_POST['account_number'] ?? ''));
        $branch = trim($_POST['branch_code'] ?? '');

        if ($holder === '' || $bank === '') {
            $this->renderForm($company, 'Account holder and bank are both required.');
            return;
        }

        // Blank means unchanged, so an admin correcting a branch code doesn't
        // have to retype an account number that is never displayed back.
        if ($number === '' && !$existing) {
            $this->renderForm($company, 'An account number is required.');
            return;
        }
        if ($number !== '' && !preg_match('/^\d{6,20}$/', $number)) {
            $this->renderForm($company, 'An account number is between 6 and 20 digits, with no spaces.');
            return;
        }
        if ($branch !== '' && !preg_match('/^\d{4,10}$/', $branch)) {
            $this->renderForm($company, 'A branch code is between 4 and 10 digits.');
            return;
        }

        $data = [
            'account_holder_name' => self::truncate($holder, 150),
            'bank_name'           => self::truncate($bank, 100),
            'branch_code'         => $branch,
            'account_type'        => ($_POST['account_type'] ?? '') === 'savings' ? 'savings' : 'cheque',
            'reference_prefix'    => strtoupper(trim($_POST['reference_prefix'] ?? '')) ?: null,
            'swift_code'          => strtoupper(trim($_POST['swift_code'] ?? '')) ?: null,
        ];

        if ($number !== '') {
            $encrypted = Crypto::encrypt($number);

            // Storing an empty or false result would silently save an account
            // that can never be read back, and the failure would surface months
            // later on an invoice.
            if (!is_string($encrypted) || $encrypted === '') {
                throw new \RuntimeException(
                    'Crypto::encrypt returned nothing. Check that the application key is set in config.'
                );
            }

            $data['account_number'] = $encrypted;
        }

        CompanyBankAccount::save((int) $company['id'], $data);

        // The account holder should be the SPV, not a person. Not blocked - a
        // newly incorporated company sometimes banks under a slightly different
        // registered name - but flagged, because an investor paying into an
        // individual's account is the worst outcome this screen can produce.
        $warning = self::namesOverlap($holder, $company['name'])
            ? null
            : 'The account holder name does not look like "' . $company['name']
                . '". Investors are told to pay this account by name, so make sure it belongs '
                . 'to the SPV and not to a person or another company.';

        // The detail deliberately excludes the account number: an audit log is
        // read by more people than the account itself should be.
        $this->tryAudit(
            'edit_company_banking',
            (int) $company['id'],
            ($existing ? 'Updated' : 'Added') . " banking details: {$data['bank_name']}, "
                . "holder {$data['account_holder_name']}"
                . ($number !== '' ? ', account number changed' : ', account number unchanged')
        );

        $this->tryFlash('success', 'Banking details saved. They appear on investor invoices only.');
        if ($warning) {
            $this->tryFlash('warning', $warning);
        }

        $this->redirect('/admin/companies/' . $company['reference'] . '/banking');
    }

    /**
     * flash() lives on the core Controller, added in the admin refresh. A
     * missing convenience must not cost the save.
     */
    private function tryFlash(string $type, string $message): void
    {
        if (method_exists($this, 'flash')) {
            $this->flash($type, $message);
            return;
        }

        // Needs nothing but a session. The layout may not render it, but the
        // information isn't lost.
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /** Same reasoning for audit(), which lives on AdminController. */
    private function tryAudit(string $action, int $companyId, string $details): void
    {
        if (method_exists($this, 'audit')) {
            $this->audit($action, 'companies', $companyId, $details);
            return;
        }

        try {
            Database::connection()->prepare(
                "INSERT INTO audit_log (actor_type, actor_id, action, entity_type, entity_id, details)
                 VALUES ('admin', ?, ?, 'companies', ?, ?)"
            )->execute([(int) ($_SESSION['admin_id'] ?? 0), $action, $companyId, $details]);
        } catch (\Throwable $e) {
            // An audit write failing must not lose a save that already happened.
            error_log('Audit write failed: ' . $e->getMessage());
        }
    }

    private function renderForm(array $company, ?string $error = null): void
    {
        $account = CompanyBankAccount::forCompany((int) $company['id']);
        $hint = null;

        if ($account && !empty($account['account_number'])) {
            try {
                $decrypted = Crypto::decrypt($account['account_number']);
                // Last four only. The full number is never sent back to a
                // browser once stored, even to an admin.
                $hint = $decrypted ? substr($decrypted, -4) : null;
            } catch (\Throwable $e) {
                // A stored value that won't decrypt - usually a changed app key -
                // must not take the page down. The form still works; the hint is
                // just absent.
                error_log('Could not decrypt banking details for ' . $company['reference'] . ': ' . $e->getMessage());
            }
        }

        $this->render('admin/companies/banking', [
            'company'    => $company,
            'account'    => $account,
            'numberHint' => $hint,
            'complete'   => CompanyBankAccount::isComplete($account),
            'banks'      => ProfileOptions::banks(),
            'error'      => $error,
            'old'        => $error !== null ? $_POST : [],
        ]);
    }

    /**
     * Truncate without needing mbstring.
     *
     * substr() alone would cut a multi-byte character in half and hand MySQL
     * invalid UTF-8, which fails the write rather than shortening the string.
     * The /u modifier makes `.` match a whole character.
     */
    private static function truncate(string $value, int $max): string
    {
        return preg_match('/^.{0,' . $max . '}/us', $value, $m) ? $m[0] : substr($value, 0, $max);
    }

    /**
     * Loose overlap between the account holder and the company name.
     *
     * Deliberately permissive - a registered name and a bank's record of it
     * differ over "(Pty) Ltd", punctuation and abbreviations - so it warns
     * rather than blocks. It catches the case that matters: an account in a
     * completely different name.
     */
    private static function namesOverlap(string $holder, string $companyName): bool
    {
        $normalise = static fn (string $v): array => array_values(array_filter(
            preg_split('/[^a-z0-9]+/', strtolower($v)) ?: [],
            static fn ($w) => strlen($w) > 2 && !in_array($w, ['pty', 'ltd', 'limited', 'the', 'inc'], true)
        ));

        $holderWords = $normalise($holder);
        $companyWords = $normalise($companyName);

        if (!$companyWords || !$holderWords) {
            return true;
        }

        return array_intersect($holderWords, $companyWords) !== [];
    }

    private function findCompanyOr404(string $reference): ?array
    {
        $company = Company::findByReference($reference);

        if (!$company) {
            http_response_code(404);
            $this->render('errors/404');
            return null;
        }

        return $company;
    }
}
