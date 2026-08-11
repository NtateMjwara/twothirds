<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\models\AdminUser;
use app\services\EmailQueueService;
use app\services\RateLimitService;

class AuthController extends AdminController
{
    public function showLogin(): void
    {
        $this->render('admin/login');
    }

    public function login(): void
    {
        $this->verifyCsrf();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $identifier = 'admin:' . strtolower($email);

        if (RateLimitService::isLocked($identifier)) {
            $minutes = RateLimitService::minutesUntilUnlock($identifier);
            $this->render('admin/login', ['error' => "Too many failed attempts. Try again in about {$minutes} minute(s)."]);
            return;
        }

        $admin = AdminUser::findByEmail($email);

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            RateLimitService::recordAttempt($identifier, false);
            $this->render('admin/login', ['error' => 'Incorrect email or password.']);
            return;
        }

        RateLimitService::recordAttempt($identifier, true);
        RateLimitService::clearAttempts($identifier);

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];

        $this->redirect('/admin');
    }

    public function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role']);
        $this->redirect('/admin/login');
    }

    public function showForgotPassword(): void
    {
        $this->render('admin/forgot-password');
    }

    public function sendResetLink(): void
    {
        $this->verifyCsrf();

        $email = trim($_POST['email'] ?? '');
        $admin = AdminUser::findByEmail($email);

        // Always show the same message whether or not the email exists,
        // so login-page probing can't be used to enumerate admin accounts.
        if ($admin) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            AdminUser::setResetToken($admin['id'], $token, $expiresAt);

            $config = require __DIR__ . '/../../config/config.php';
            $resetUrl = $config['app']['url'] . '/admin/reset-password/' . $token;

            EmailQueueService::send(
                $admin['email'],
                $admin['full_name'],
                'Reset your admin password',
                "<p>Click below to reset your password. This link expires in 1 hour.</p>
                 <p><a href=\"{$resetUrl}\">{$resetUrl}</a></p>"
            );
        }

        $this->render('admin/forgot-password', [
            'sent' => true,
        ]);
    }

    public function showResetPassword(string $token): void
    {
        $admin = AdminUser::findByResetToken($token);
        if (!$admin) {
            $this->render('admin/reset-password', ['invalid' => true]);
            return;
        }

        $this->render('admin/reset-password', ['token' => $token]);
    }

    public function resetPassword(string $token): void
    {
        $this->verifyCsrf();

        $admin = AdminUser::findByResetToken($token);
        if (!$admin) {
            $this->render('admin/reset-password', ['invalid' => true]);
            return;
        }

        $password = $_POST['password'] ?? '';
        if (strlen($password) < 10) {
            $this->render('admin/reset-password', ['token' => $token, 'error' => 'Password must be at least 10 characters.']);
            return;
        }

        AdminUser::update($admin['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        AdminUser::clearResetToken($admin['id']);

        $this->redirect('/admin/login');
    }
}
