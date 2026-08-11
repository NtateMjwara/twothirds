<?php
namespace app\controllers;

use app\core\Controller;
use app\core\Crypto;
use app\core\Database;
use app\models\Shareholding;
use app\models\Commitment;
use app\models\UserProfile;
use app\models\UserAddress;
use app\models\UserKyc;
use app\models\UserBankAccount;
use app\models\Notification;
use app\models\Watchlist;
use app\services\KycService;

class AccountController extends Controller
{
    public function portfolio(): void
    {
        $this->requireAuth();

        $this->render('account/portfolio', [
            'holdings' => Shareholding::forUser((int) $_SESSION['user_id']),
            'pending'  => Commitment::pendingForUser((int) $_SESSION['user_id']),
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

    public function showProfile(): void
    {
        $this->requireAuth();
        $this->render('account/profile', [
            'profile' => UserProfile::forUser((int) $_SESSION['user_id']),
        ]);
    }

    public function updateProfile(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = (int) $_SESSION['user_id'];
        $profile = UserProfile::forUser($userId);

        UserProfile::update((int) $profile['id'], [
            'first_name'    => trim($_POST['first_name'] ?? ''),
            'last_name'     => trim($_POST['last_name'] ?? ''),
            'phone'         => trim($_POST['phone'] ?? ''),
            'date_of_birth' => $_POST['date_of_birth'] ?: null,
        ]);

        $_SESSION['user_name'] = trim($_POST['first_name'] ?? '');

        $this->render('account/profile', [
            'profile' => UserProfile::forUser($userId),
            'saved'   => true,
        ]);
    }

    public function showAddress(): void
    {
        $this->requireAuth();
        $this->render('account/address', [
            'addresses' => UserAddress::forUser((int) $_SESSION['user_id']),
        ]);
    }

    public function updateAddress(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = (int) $_SESSION['user_id'];
        $type = ($_POST['address_type'] ?? '') === 'postal' ? 'postal' : 'residential';
        $existing = UserAddress::forUserAndType($userId, $type);

        $data = [
            'address_line1' => trim($_POST['address_line1'] ?? ''),
            'address_line2' => trim($_POST['address_line2'] ?? ''),
            'city'          => trim($_POST['city'] ?? ''),
            'province'      => trim($_POST['province'] ?? ''),
            'postal_code'   => trim($_POST['postal_code'] ?? ''),
            'country'       => trim($_POST['country'] ?? '') ?: 'South Africa',
        ];

        if ($existing) {
            UserAddress::update((int) $existing['id'], $data);
        } else {
            UserAddress::create($data + ['user_id' => $userId, 'address_type' => $type]);
        }

        $this->render('account/address', [
            'addresses' => UserAddress::forUser($userId),
            'saved'     => true,
        ]);
    }

    public function showKyc(): void
    {
        $this->requireAuth();
        $this->render('account/kyc', [
            'kyc' => UserKyc::forUser((int) $_SESSION['user_id']),
        ]);
    }

    public function submitKyc(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = (int) $_SESSION['user_id'];
        $idType = ($_POST['id_type'] ?? '') === 'passport' ? 'passport' : 'sa_id';
        $idNumber = trim($_POST['id_number'] ?? '');

        if ($idNumber === '' || empty($_FILES['id_document']['tmp_name'])) {
            $this->render('account/kyc', [
                'kyc'   => UserKyc::forUser($userId),
                'error' => 'ID number and a document are both required.',
            ]);
            return;
        }

        try {
            KycService::submit($userId, $idType, $idNumber, $_FILES['id_document']);
        } catch (\InvalidArgumentException $e) {
            $this->render('account/kyc', ['kyc' => UserKyc::forUser($userId), 'error' => $e->getMessage()]);
            return;
        }

        $this->render('account/kyc', ['kyc' => UserKyc::forUser($userId), 'saved' => true]);
    }

    public function showBankAccount(): void
    {
        $this->requireAuth();
        $account = UserBankAccount::primaryForUser((int) $_SESSION['user_id']);

        $this->render('account/bank', [
            'account'      => $account,
            'maskedNumber' => $this->maskedAccountNumber($account),
        ]);
    }

    public function updateBankAccount(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = (int) $_SESSION['user_id'];
        $existing = UserBankAccount::primaryForUser($userId);

        // Blank means "unchanged" - the view never re-displays the real number,
        // so an empty submission must not overwrite it with an empty string.
        $accountNumberInput = trim($_POST['account_number'] ?? '');
        $encryptedAccountNumber = ($accountNumberInput === '' && $existing)
            ? $existing['account_number']
            : Crypto::encrypt($accountNumberInput);

        $data = [
            'account_holder_name' => trim($_POST['account_holder_name'] ?? ''),
            'bank_name'           => trim($_POST['bank_name'] ?? ''),
            'account_number'      => $encryptedAccountNumber,
            'branch_code'         => trim($_POST['branch_code'] ?? ''),
            'account_type'        => ($_POST['account_type'] ?? '') === 'savings' ? 'savings' : 'cheque',
        ];

        if ($existing) {
            UserBankAccount::update((int) $existing['id'], $data);
        } else {
            UserBankAccount::create($data + ['user_id' => $userId, 'is_primary' => 1]);
        }

        $account = UserBankAccount::primaryForUser($userId);

        $this->render('account/bank', [
            'account'      => $account,
            'maskedNumber' => $this->maskedAccountNumber($account),
            'saved'        => true,
        ]);
    }

    private function maskedAccountNumber(?array $account): ?string
    {
        if (!$account) {
            return null;
        }

        $decrypted = Crypto::decrypt($account['account_number']);
        return $decrypted ? substr($decrypted, -4) : '????';
    }

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
        // No longer auto-marks everything read on view - the unread badge in
        // account-nav.php now reflects what's actually been acknowledged, not
        // just what's been looked at once.
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
}
