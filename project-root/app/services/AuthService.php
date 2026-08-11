<?php
namespace app\services;

use app\core\Database;
use app\models\User;
use app\models\UserProfile;

class AuthService
{
    public static function register(string $email, string $password, string $firstName, string $lastName, string $phone): int
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $userId = User::create([
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'status'        => 'active',
            ]);

            UserProfile::create([
                'user_id'    => $userId,
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'phone'      => $phone,
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e; // exits here on failure - the email below never fires
        }

        self::sendVerificationEmail($userId, $email, $firstName);

        return $userId;
    }

    public static function sendVerificationEmail(int $userId, string $email, string $firstName): void
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        User::setVerificationToken($userId, $token, $expiresAt);

        $config = require __DIR__ . '/../config/config.php';
        $verifyUrl = $config['app']['url'] . '/verify-email/' . $token;

        EmailQueueService::send(
            $email,
            $firstName,
            'Verify your email address',
            "<p>Welcome! Click below to verify your email address.</p>
             <p><a href=\"{$verifyUrl}\">{$verifyUrl}</a></p>
             <p>This link expires in 24 hours.</p>"
        );
    }
}
