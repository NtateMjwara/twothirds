<?php
namespace app\controllers;

use app\core\Controller;
use app\models\User;
use app\models\UserProfile;
use app\services\AuthService;
use app\services\RateLimitService;
use app\services\EmailQueueService;

class AuthController extends Controller
{
    public function showRegister(): void
    {
        $this->render('auth/register');
    }

    public function register(): void
    {
        $this->verifyCsrf();

        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');

        if (User::findByEmail($email)) {
            $this->render('auth/register', ['error' => 'An account with that email already exists.']);
            return;
        }
        if (strlen($password) < 10 || $firstName === '' || $lastName === '') {
            $this->render('auth/register', ['error' => 'Please fill in all fields. Password must be at least 10 characters.']);
            return;
        }

        AuthService::register($email, $password, $firstName, $lastName, $phone);

        // No auto-login anymore - verification is required before any session
        // can start, so there's nothing to log in to yet.
        $this->render('auth/check-email', ['email' => $email]);
    }

    public function showLogin(): void
    {
        $this->render('auth/login');
    }

    public function login(): void
    {
        $this->verifyCsrf();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $identifier = 'investor:' . strtolower($email);

        if (RateLimitService::isLocked($identifier)) {
            $minutes = RateLimitService::minutesUntilUnlock($identifier);
            $this->render('auth/login', ['error' => "Too many failed attempts. Try again in about {$minutes} minute(s)."]);
            return;
        }

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            RateLimitService::recordAttempt($identifier, false);
            $this->render('auth/login', ['error' => 'Incorrect email or password.']);
            return;
        }

        if (empty($user['email_verified_at'])) {
            // Correct password, so this isn't a brute-force signal - don't
            // count it against the rate limit, just block the session.
            $this->render('auth/login', [
                'error'           => 'Please verify your email before logging in.',
                'unverifiedEmail' => $email,
            ]);
            return;
        }

        RateLimitService::recordAttempt($identifier, true);
        RateLimitService::clearAttempts($identifier);

        $profile = UserProfile::forUser((int) $user['id']);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $profile['first_name'] ?? '';

        $this->redirect('/account/portfolio');
    }

    public function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['user_name']);
        $this->redirect('/login');
    }

    public function verifyEmail(string $token): void
    {
        $user = User::findByVerificationToken($token);

        if (!$user) {
            $this->render('auth/verify-email', ['invalid' => true]);
            return;
        }

        User::markEmailVerified((int) $user['id']);
        $this->render('auth/verify-email', ['verified' => true]);
    }

    public function showResendVerification(): void
    {
        $this->render('auth/resend-verification');
    }

    public function resendVerification(): void
    {
        $this->verifyCsrf();

        $email = trim($_POST['email'] ?? '');
        $user = User::findByEmail($email);

        // Same response regardless of whether the account exists or is
        // already verified - avoids leaking which emails are registered.
        if ($user && empty($user['email_verified_at'])) {
            $profile = UserProfile::forUser((int) $user['id']);
            AuthService::sendVerificationEmail((int) $user['id'], $email, $profile['first_name'] ?? '');
        }

        $this->render('auth/resend-verification', ['sent' => true]);
    }

    public function showForgotPassword(): void
    {
        $this->render('auth/forgot-password');
    }

    public function sendResetLink(): void
    {
        $this->verifyCsrf();

        $email = trim($_POST['email'] ?? '');
        $user = User::findByEmail($email);

        // Same response either way - don't reveal whether the email is registered.
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            User::setResetToken((int) $user['id'], $token, $expiresAt);

            $config = require __DIR__ . '/../config/config.php';
            $resetUrl = $config['app']['url'] . '/reset-password/' . $token;
            $profile = UserProfile::forUser((int) $user['id']);

            EmailQueueService::send(
                $email,
                $profile['first_name'] ?? '',
                'Reset your password',
                "<p>Click below to reset your password. This link expires in 1 hour.</p>
                 <p><a href=\"{$resetUrl}\">{$resetUrl}</a></p>"
            );
        }

        $this->render('auth/forgot-password', ['sent' => true]);
    }

    public function showResetPassword(string $token): void
    {
        $user = User::findByResetToken($token);
        if (!$user) {
            $this->render('auth/reset-password', ['invalid' => true]);
            return;
        }

        $this->render('auth/reset-password', ['token' => $token]);
    }

    public function resetPassword(string $token): void
    {
        $this->verifyCsrf();

        $user = User::findByResetToken($token);
        if (!$user) {
            $this->render('auth/reset-password', ['invalid' => true]);
            return;
        }

        $password = $_POST['password'] ?? '';
        if (strlen($password) < 10) {
            $this->render('auth/reset-password', ['token' => $token, 'error' => 'Password must be at least 10 characters.']);
            return;
        }

        User::update((int) $user['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        User::clearResetToken((int) $user['id']);

        $this->redirect('/login');
    }
}
