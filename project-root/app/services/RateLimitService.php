<?php
namespace app\services;

use app\core\Database;

class RateLimitService
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;

    public static function isLocked(string $identifier): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) AS c FROM login_attempts
             WHERE identifier = ? AND successful = 0
               AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->execute([$identifier, self::WINDOW_MINUTES]);
        return (int) $stmt->fetch()['c'] >= self::MAX_ATTEMPTS;
    }

    public static function recordAttempt(string $identifier, bool $successful): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        Database::connection()->prepare(
            "INSERT INTO login_attempts (identifier, ip_address, successful) VALUES (?, ?, ?)"
        )->execute([$identifier, $ip, $successful ? 1 : 0]);
    }

    // Called on successful login so a legitimate user isn't left locked out by
    // old failed attempts sitting inside the window.
    public static function clearAttempts(string $identifier): void
    {
        Database::connection()->prepare(
            "DELETE FROM login_attempts WHERE identifier = ?"
        )->execute([$identifier]);
    }

    public static function minutesUntilUnlock(string $identifier): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT MIN(attempted_at) AS oldest FROM login_attempts
             WHERE identifier = ? AND successful = 0
               AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->execute([$identifier, self::WINDOW_MINUTES]);
        $oldest = $stmt->fetch()['oldest'];

        if (!$oldest) {
            return 0;
        }

        $unlockAt = strtotime($oldest) + (self::WINDOW_MINUTES * 60);
        return max(0, (int) ceil(($unlockAt - time()) / 60));
    }
}
