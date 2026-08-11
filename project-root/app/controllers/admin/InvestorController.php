<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\core\Database;
use app\models\User;
use app\models\UserProfile;
use app\models\UserKyc;
use app\models\UserBankAccount;
use app\models\Shareholding;
use app\models\Commitment;

class InvestorController extends AdminController
{
    public function index(): void
    {
        $this->requireAdmin();

        $q = trim($_GET['q'] ?? '');

        $sql = "SELECT u.id, u.email, u.status, u.email_verified_at, p.first_name, p.last_name,
                       k.status AS kyc_status
                FROM users u
                LEFT JOIN user_profiles p ON p.user_id = u.id
                LEFT JOIN user_kyc k ON k.user_id = u.id";

        $params = [];
        if ($q !== '') {
            $sql .= " WHERE u.email LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ?";
            $like = "%{$q}%";
            $params = [$like, $like, $like];
        }
        $sql .= " ORDER BY u.created_at DESC";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        $this->render('admin/investors/index', ['investors' => $stmt->fetchAll(), 'q' => $q]);
    }

    public function show(string $id): void
    {
        $this->requireAdmin();

        $user = User::find((int) $id);
        if (!$user) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $this->render('admin/investors/show', [
            'user'        => $user,
            'profile'     => UserProfile::forUser((int) $id),
            'kyc'         => UserKyc::forUser((int) $id),
            // Bank account presence only - never decrypt the number for a general support view.
            'bankAccount' => UserBankAccount::primaryForUser((int) $id),
            'holdings'    => Shareholding::forUser((int) $id),
            'commitments' => Commitment::pendingForUser((int) $id),
        ]);
    }
}
