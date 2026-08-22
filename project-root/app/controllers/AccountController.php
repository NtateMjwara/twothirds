<?php
namespace app\controllers;

use app\core\Controller;
use app\core\Crypto;
use app\core\Database;
use app\models\Shareholding;
use app\models\Commitment;
use app\models\User;
use app\models\UserProfile;
use app\models\UserAddress;
use app\models\UserKyc;
use app\models\UserTaxDetail;
use app\models\UserBankAccount;
use app\models\Notification;
use app\models\Watchlist;
use app\services\KycService;
use app\services\PortfolioService;
use app\services\DiscoveryService;
use app\services\ProfileOptions;

class AccountController extends Controller
{
    // ============================================================
    // Portfolio
    // ============================================================

    public function portfolio(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];

        // Holdings are aggregated per company rather than per ledger row: the
        // share register is append-only, so one company can be several rows and
        // the investor should see one line.
        $holdings = PortfolioService::holdings($userId);

        $this->render('account/portfolio', [
            'holdings'   => $holdings,
            'totals'     => PortfolioService::totals($holdings),
            'allocation' => PortfolioService::allocation($holdings),
            'income'     => PortfolioService::attributableIncome($userId, $holdings),
            'pending'    => Commitment::pendingForUser($userId),
            // Gives the empty state somewhere concrete to send someone, and the
            // rail a live number rather than a static "browse" link.
            'openOfferings' => DiscoveryService::listings(
                ['availability' => 'open'] + DiscoveryService::emptyFilters(),
                1,
                1
            )['total'],
        ]);
    }

    public function withdrawCommitment(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        // The WHERE clause is the real guard here - it only touches a row that is
        // both this investor's own and still pending, so there's no way to withdraw
        // someone else's commitment or one that's already been settled.
        Database::connection()->prepare(
            "UPDATE commitments SET status = 'withdrawn' WHERE id = ? AND user_id = ? AND status = 'pending'"
        )->execute([(int) $id, (int) $_SESSION['user_id']]);

        $this->redirect('/account/portfolio');
    }

    // ============================================================
    // Personal details
    // ============================================================

    public function showProfile(): void
    {
        $this->requireAuth();
        $this->renderProfile();
    }

    public function updateProfile(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = (int) $_SESSION['user_id'];
        $profile = UserProfile::forUser($userId);

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $dob = ($_POST['date_of_birth'] ?? '') ?: null;

        if ($firstName === '' || $lastName === '') {
            $this->renderProfile('First and last name are both required.');
            return;
        }

        // A date of birth in the future, or one implying an age under 18, is
        // either a typo or someone who cannot open an account. Both are worth
        // catching before the KYC reviewer sees it.
        if ($dob !== null) {
            $timestamp = strtotime($dob);
            if ($timestamp === false || $timestamp > time()) {
                $this->renderProfile('That date of birth is in the future.');
                return;
            }
            if ($timestamp > strtotime('-18 years')) {
                $this->renderProfile('You must be at least 18 to hold shares on this platform.');
                return;
            }
        }

        // Every select is re-checked against its own list. The dropdown
        // constrains a browser, not a request.
        $data = [
            'title'                  => $this->pick($_POST['title'] ?? '', ProfileOptions::titles()),
            'first_name'             => $firstName,
            'initials'               => ProfileOptions::upper(trim($_POST['initials'] ?? '')) ?: null,
            'preferred_name'         => trim($_POST['preferred_name'] ?? '') ?: null,
            'last_name'              => $lastName,
            'date_of_birth'          => $dob,
            'gender'                 => $this->pick($_POST['gender'] ?? '', ProfileOptions::genders()),
            'phone'                  => trim($_POST['phone'] ?? ''),
            'calling_code'           => $this->pick($_POST['calling_code'] ?? '', ProfileOptions::callingCodes()) ?? '+27',
            'work_calling_code'      => $this->pick($_POST['work_calling_code'] ?? '', ProfileOptions::callingCodes()),
            'work_phone'             => trim($_POST['work_phone'] ?? '') ?: null,
            'country_of_birth'       => $this->pick($_POST['country_of_birth'] ?? '', ProfileOptions::countries()),
            'city_of_birth'          => trim($_POST['city_of_birth'] ?? '') ?: null,
            'country_of_residence'   => $this->pick($_POST['country_of_residence'] ?? '', ProfileOptions::countries()),
            'country_of_citizenship' => $this->pick($_POST['country_of_citizenship'] ?? '', ProfileOptions::countries()),
            'marital_status'         => $this->pick($_POST['marital_status'] ?? '', ProfileOptions::maritalStatuses()),
        ];

        if ($profile) {
            UserProfile::update((int) $profile['id'], $data);
        } else {
            UserProfile::create($data + ['user_id' => $userId]);
        }

        // The header greets by preferred name where one is given.
        $_SESSION['user_name'] = $data['preferred_name'] ?: $firstName;

        $this->flash('success', 'Personal details saved.');
        $this->redirect('/account/profile');
    }

    // ============================================================
    // Address
    // ============================================================

    public function showAddress(): void
    {
        $this->requireAuth();
        $this->renderAddress();
    }

    public function updateAddress(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = (int) $_SESSION['user_id'];
        $type = ($_POST['address_type'] ?? '') === 'postal' ? 'postal' : 'residential';

        $line1 = trim($_POST['address_line1'] ?? '');
        if ($line1 === '') {
            $this->renderAddress('A street address is required.');
            return;
        }

        $data = [
            'address_line1' => $line1,
            'address_line2' => trim($_POST['address_line2'] ?? ''),
            'suburb'        => trim($_POST['suburb'] ?? '') ?: null,
            'city'          => trim($_POST['city'] ?? ''),
            'province'      => $this->pick($_POST['province'] ?? '', ProfileOptions::provinces()),
            'postal_code'   => trim($_POST['postal_code'] ?? ''),
            'country'       => $this->pick($_POST['country'] ?? '', ProfileOptions::countries()) ?? 'South Africa',
        ];

        $existing = UserAddress::forUserAndType($userId, $type);

        if ($existing) {
            UserAddress::update((int) $existing['id'], $data);
        } else {
            UserAddress::create($data + ['user_id' => $userId, 'address_type' => $type]);
        }

        // Copying the residential address to postal is the common case, so it's
        // a checkbox rather than making people type it twice.
        if ($type === 'residential' && !empty($_POST['postal_same'])) {
            $postal = UserAddress::forUserAndType($userId, 'postal');
            if ($postal) {
                UserAddress::update((int) $postal['id'], $data);
            } else {
                UserAddress::create($data + ['user_id' => $userId, 'address_type' => 'postal']);
            }
        }

        $this->flash('success', ucfirst($type) . ' address saved.');
        $this->redirect('/account/address');
    }

    // ============================================================
    // KYC / FICA
    // ============================================================

    public function showKyc(): void
    {
        $this->requireAuth();
        $this->renderKyc();
    }

    public function submitKyc(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = (int) $_SESSION['user_id'];
        $existing = UserKyc::forUser($userId);

        // Once verified, the identity block is closed. Letting someone edit the
        // ID number on a verified record would silently invalidate a review
        // that has already been done.
        if ($existing && $existing['status'] === 'verified') {
            $this->updateFicaOnly($userId, $existing);
            return;
        }

        $idType = ($_POST['id_type'] ?? '') === 'passport' ? 'passport' : 'sa_id';
        $idNumber = trim($_POST['id_number'] ?? '');

        if ($idNumber === '') {
            $this->renderKyc('An ID or passport number is required.');
            return;
        }
        if ($idType === 'sa_id' && !preg_match('/^\d{13}$/', $idNumber)) {
            $this->renderKyc('A South African ID number is 13 digits. Choose Passport if that is what you are entering.');
            return;
        }

        $ficaError = $this->validateFica();
        if ($ficaError !== null) {
            $this->renderKyc($ficaError);
            return;
        }

        // A document is required on a first submission and after a rejection,
        // but not when someone is only correcting the FICA block on a pending
        // record - re-uploading an identical scan helps nobody.
        $hasUpload = !empty($_FILES['id_document']['tmp_name']);
        if (!$hasUpload && (!$existing || empty($existing['document_id']))) {
            $this->renderKyc('Upload a copy of your ID or passport.');
            return;
        }

        try {
            if ($hasUpload) {
                KycService::submit($userId, $idType, $idNumber, $_FILES['id_document']);
            } else {
                UserKyc::update((int) $existing['id'], [
                    'id_type'   => $idType,
                    'id_number' => $idNumber,
                    'status'    => 'pending',
                ]);
            }

            UserKyc::update((int) UserKyc::forUser($userId)['id'], $this->ficaFields());
        } catch (\InvalidArgumentException $e) {
            $this->renderKyc($e->getMessage());
            return;
        } catch (\Throwable $e) {
            error_log('KYC submission failed: ' . $e->getMessage());
            $this->renderKyc('Your submission could not be saved. Nothing was changed.');
            return;
        }

        $this->flash('success', 'Submitted for review. Verification usually takes a working day or two.');
        $this->redirect('/account/kyc');
    }

    /** Verified investors can still keep employment and income details current. */
    private function updateFicaOnly(int $userId, array $existing): void
    {
        $error = $this->validateFica();
        if ($error !== null) {
            $this->renderKyc($error);
            return;
        }

        UserKyc::update((int) $existing['id'], $this->ficaFields());

        $this->flash('success', 'Employment and income details updated. Your verified status is unaffected.');
        $this->redirect('/account/kyc');
    }

    private function ficaFields(): array
    {
        return [
            'source_of_income'     => $this->pick($_POST['source_of_income'] ?? '', ProfileOptions::incomeSources()),
            'account_funds_source' => $this->pick($_POST['account_funds_source'] ?? '', ProfileOptions::fundSources()),
            'occupation'           => $this->pick($_POST['occupation'] ?? '', ProfileOptions::occupations()),
            'employer'             => trim($_POST['employer'] ?? '') ?: null,
            'industry'             => $this->pick($_POST['industry'] ?? '', ProfileOptions::industries()),
            'annual_income_band'   => $this->pick($_POST['annual_income_band'] ?? '', ProfileOptions::incomeBands()),
        ];
    }

    private function validateFica(): ?string
    {
        $fields = $this->ficaFields();

        foreach ([
            'source_of_income'     => 'source of income',
            'account_funds_source' => 'source of the funds for this account',
            'occupation'           => 'employment status',
            'annual_income_band'   => 'annual income band',
            'industry'             => 'industry',
        ] as $key => $label) {
            if ($fields[$key] === null) {
                return 'Please choose your ' . $label . '.';
            }
        }

        // Employer is only meaningful for someone who has one. Demanding it from
        // a retired or unemployed investor produces a junk value, not a record.
        $needsEmployer = in_array($fields['occupation'], ['employed_full', 'employed_part', 'director'], true);
        if ($needsEmployer && $fields['employer'] === null) {
            return 'Please enter your employer name.';
        }

        return null;
    }

    // ============================================================
    // Tax
    // ============================================================

    public function showTax(): void
    {
        $this->requireAuth();
        $this->renderTax();
    }

    public function updateTax(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = (int) $_SESSION['user_id'];
        $existing = UserTaxDetail::forUser($userId);
        $isResident = !empty($_POST['is_sa_tax_resident']);

        $taxNumber = trim($_POST['tax_number'] ?? '');
        $foreignNumber = trim($_POST['foreign_tax_number'] ?? '');
        $noTinReason = trim($_POST['no_tin_reason'] ?? '');

        if ($isResident && $taxNumber === '' && $noTinReason === '' && !$existing) {
            $this->renderTax('Enter your SARS tax number, or say why you do not have one.');
            return;
        }
        if ($taxNumber !== '' && !preg_match('/^\d{10}$/', $taxNumber)) {
            $this->renderTax('A SARS tax reference number is 10 digits.');
            return;
        }
        if (!$isResident && $foreignNumber === '' && $noTinReason === '') {
            $this->renderTax('Enter your foreign tax number, or say why you do not have one.');
            return;
        }

        // Blank means unchanged, because the form never redisplays the real
        // number - the same rule the bank account number follows.
        $data = [
            'is_sa_tax_resident'  => $isResident ? 1 : 0,
            'foreign_tax_country' => $isResident ? null : $this->pick($_POST['foreign_tax_country'] ?? '', ProfileOptions::countries()),
            'no_tin_reason'       => $noTinReason ?: null,
        ];

        if ($taxNumber !== '') {
            $data['tax_number'] = Crypto::encrypt($taxNumber);
        }
        if ($foreignNumber !== '') {
            $data['foreign_tax_number'] = Crypto::encrypt($foreignNumber);
        }
        if ($isResident) {
            $data['foreign_tax_number'] = null;
        }

        UserTaxDetail::save($userId, $data);

        $this->flash('success', 'Tax details saved.');
        $this->redirect('/account/tax');
    }

    // ============================================================
    // Bank accounts
    // ============================================================

    public function showBankAccounts(): void
    {
        $this->requireAuth();
        $this->renderBank();
    }

    public function storeBankAccount(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = (int) $_SESSION['user_id'];
        $currency = 'ZAR';

        if (UserBankAccount::countForCurrency($userId, $currency) >= ProfileOptions::MAX_ACCOUNTS_PER_CURRENCY) {
            $this->renderBank('You can hold at most ' . ProfileOptions::MAX_ACCOUNTS_PER_CURRENCY
                . ' ' . $currency . ' accounts. Remove one before adding another.');
            return;
        }

        $holder = trim($_POST['account_holder_name'] ?? '');
        $bank = $this->pick($_POST['bank_name'] ?? '', array_keys(ProfileOptions::banks()));
        $number = trim($_POST['account_number'] ?? '');
        $branch = trim($_POST['branch_code'] ?? '');

        if ($holder === '' || $bank === null || $number === '') {
            $this->renderBank('Account holder, bank and account number are all required.');
            return;
        }
        if (!preg_match('/^\d{6,20}$/', $number)) {
            $this->renderBank('An account number is between 6 and 20 digits, with no spaces.');
            return;
        }

        // The name check is a real anti-fraud control, not a formality: money
        // only ever leaves to an account in the holder's own name. Compared
        // loosely because middle names and initials vary between documents.
        $profile = UserProfile::forUser($userId);
        if ($profile && !$this->nameLooksLikeHolder($holder, $profile)) {
            $this->renderBank(
                'The account holder name must match the name on your TwoThirds account ('
                . trim($profile['first_name'] . ' ' . $profile['last_name'])
                . '). Third-party accounts cannot be used.'
            );
            return;
        }

        UserBankAccount::create([
            'user_id'             => $userId,
            'account_holder_name' => $holder,
            'bank_name'           => $bank,
            'account_number'      => Crypto::encrypt($number),
            'branch_code'         => $branch ?: (ProfileOptions::banks()[$bank] ?? ''),
            'account_type'        => ($_POST['account_type'] ?? '') === 'savings' ? 'savings' : 'cheque',
            'currency'            => $currency,
            'status'              => 'pending',
            // First account for this user becomes the primary automatically.
            'is_primary'          => UserBankAccount::primaryForUser($userId) ? 0 : 1,
        ]);

        $this->flash('success', 'Bank account added. It will show as pending until we have verified it.');
        $this->redirect('/account/bank');
    }

    public function makeBankAccountPrimary(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = (int) $_SESSION['user_id'];
        $account = UserBankAccount::findForUser((int) $id, $userId);

        if (!$account) {
            $this->flash('error', 'That account is not on your profile.');
        } elseif ($account['status'] !== 'verified') {
            // Paying into an unverified account is the thing verification exists
            // to prevent, so it cannot be made the payout destination.
            $this->flash('warning', 'An account has to be verified before it can receive payouts.');
        } else {
            UserBankAccount::makePrimary((int) $account['id'], $userId);
            $this->flash('success', 'Payouts will now go to your ' . $account['bank_name'] . ' account.');
        }

        $this->redirect('/account/bank');
    }

    public function deleteBankAccount(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = (int) $_SESSION['user_id'];
        $account = UserBankAccount::findForUser((int) $id, $userId);

        if (!$account) {
            $this->flash('error', 'That account is not on your profile.');
            $this->redirect('/account/bank');
            return;
        }

        UserBankAccount::deleteForUser((int) $account['id'], $userId);

        // Removing the primary would otherwise leave a user with accounts but no
        // payout destination.
        if ((int) $account['is_primary'] === 1) {
            $next = UserBankAccount::primaryForUser($userId);
            if ($next && $next['status'] === 'verified') {
                UserBankAccount::makePrimary((int) $next['id'], $userId);
            }
        }

        $this->flash('success', 'Bank account removed.');
        $this->redirect('/account/bank');
    }

    // ============================================================
    // Watchlist and notifications
    // ============================================================

    public function watchlist(): void
    {
        $this->requireAuth();
        $this->render('account/watchlist', [
            'watchlist' => Watchlist::forUser((int) $_SESSION['user_id']),
        ]);
    }

    public function notifications(): void
    {
        $this->requireAuth();
        $this->render('account/notifications', [
            'notifications' => Notification::forUser((int) $_SESSION['user_id']),
        ]);
    }

    public function markNotificationRead(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        Notification::markRead((int) $id, (int) $_SESSION['user_id']);
        $this->redirect('/account/notifications');
    }

    public function markAllNotificationsRead(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        Notification::markAllRead((int) $_SESSION['user_id']);
        $this->redirect('/account/notifications');
    }

    // ============================================================
    // Rendering
    // ============================================================

    private function renderProfile(?string $error = null): void
    {
        $this->render('account/profile', $this->accountContext() + [
            'error' => $error,
            'old'   => $error !== null ? $_POST : null,
        ]);
    }

    private function renderAddress(?string $error = null): void
    {
        $userId = (int) $_SESSION['user_id'];
        $this->render('account/address', $this->accountContext() + [
            'residential' => UserAddress::forUserAndType($userId, 'residential'),
            'postal'      => UserAddress::forUserAndType($userId, 'postal'),
            'error'       => $error,
        ]);
    }

    private function renderKyc(?string $error = null): void
    {
        $this->render('account/kyc', $this->accountContext() + [
            'error' => $error,
            'old'   => $error !== null ? $_POST : null,
        ]);
    }

    private function renderTax(?string $error = null): void
    {
        $userId = (int) $_SESSION['user_id'];
        $tax = UserTaxDetail::forUser($userId);

        $this->render('account/tax', $this->accountContext() + [
            'tax' => $tax,
            // Only ever the last four. The full number is never sent back to a
            // browser once stored.
            'taxNumberHint' => $this->lastFour($tax['tax_number'] ?? null),
            'foreignNumberHint' => $this->lastFour($tax['foreign_tax_number'] ?? null),
            'error' => $error,
        ]);
    }

    private function renderBank(?string $error = null): void
    {
        $userId = (int) $_SESSION['user_id'];
        $accounts = UserBankAccount::forUser($userId);

        foreach ($accounts as &$account) {
            $account['masked'] = $this->maskedAccountNumber($account);
        }
        unset($account);

        $this->render('account/bank', $this->accountContext() + [
            'accounts' => $accounts,
            'atLimit'  => UserBankAccount::countForCurrency($userId, 'ZAR') >= ProfileOptions::MAX_ACCOUNTS_PER_CURRENCY,
            'error'    => $error,
            'old'      => $error !== null ? $_POST : null,
        ]);
    }

    /**
     * The header band and completion state, needed by every tab.
     *
     * Assembled once here so the tabs can show which sections are still
     * outstanding - a profile that is 3 of 5 complete should say so on every
     * page, not only on the one that's missing.
     */
    private function accountContext(): array
    {
        $userId = (int) $_SESSION['user_id'];
        $user = User::find($userId);
        $profile = UserProfile::forUser($userId);
        $kyc = UserKyc::forUser($userId);
        $tax = UserTaxDetail::forUser($userId);
        $accounts = UserBankAccount::forUser($userId);
        $residential = UserAddress::forUserAndType($userId, 'residential');

        $sections = [
            'profile' => $profile !== null && !empty($profile['date_of_birth']),
            'address' => $residential !== null,
            'kyc'     => $kyc !== null && $kyc['status'] === 'verified',
            'tax'     => $tax !== null,
            'bank'    => $accounts !== [],
        ];

        return [
            'user'        => $user,
            'profile'     => $profile,
            'kyc'         => $kyc,
            'complete'    => $sections,
            'completeCount' => count(array_filter($sections)),
            'sectionCount'  => count($sections),
        ];
    }

    // ============================================================
    // Helpers
    // ============================================================

    /** Returns the submitted value only if it is in the allowed list. */
    private function pick(string $value, array $options): ?string
    {
        return ProfileOptions::isValid($value, $options) ? $value : null;
    }

    private function maskedAccountNumber(array $account): string
    {
        $decrypted = Crypto::decrypt($account['account_number']);
        if ($decrypted === null) {
            // Wrong key or corrupted data. Saying so beats printing "????" and
            // letting someone believe their account is on file correctly.
            return 'unreadable';
        }
        return str_repeat('•', max(0, strlen($decrypted) - 4)) . substr($decrypted, -4);
    }

    private function lastFour(?string $encrypted): ?string
    {
        if (!$encrypted) {
            return null;
        }
        $decrypted = Crypto::decrypt($encrypted);
        return $decrypted ? substr($decrypted, -4) : null;
    }

    /**
     * Loose comparison of the bank account holder against the profile name.
     *
     * Deliberately permissive: documents disagree about middle names, initials
     * and hyphens, and a false rejection here blocks a legitimate withdrawal.
     * It catches the case that matters - an account in a visibly different
     * person's name - and leaves the rest to the human verification step.
     */
    private function nameLooksLikeHolder(string $holder, array $profile): bool
    {
        $normalise = static fn (string $value): string =>
            preg_replace('/[^a-z]/', '', strtolower($value));

        $holderNormalised = $normalise($holder);
        $surname = $normalise($profile['last_name'] ?? '');
        $firstName = $normalise($profile['first_name'] ?? '');

        if ($surname === '' || $holderNormalised === '') {
            return true;
        }

        // The surname must appear, plus either the first name or its initial.
        if (!str_contains($holderNormalised, $surname)) {
            return false;
        }

        return str_contains($holderNormalised, $firstName)
            || ($firstName !== '' && str_contains($holderNormalised, $firstName[0]));
    }
}
