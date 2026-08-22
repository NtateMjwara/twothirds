<?php
namespace app\services;

use app\core\Database;

/**
 * Everything the navigation bar needs, in one query.
 *
 * The layout previously called Notification::unreadCount() on every page render,
 * and a profile dropdown needs the name, the email and the verification status
 * as well. Four model calls in a layout is four round trips on every page of the
 * site, including pages nobody signed in ever sees.
 *
 * Cached per request because the layout is rendered once but a partial may ask
 * for this more than once - the mobile panel and the desktop dropdown show the
 * same information.
 */
class NavContext
{
    private static ?array $cache = null;

    /**
     * @return array{
     *   signed_in: bool, id: int, email: string, name: string, full_name: string,
     *   initials: string, kyc_status: ?string, unread: int, verified: bool,
     *   needs_attention: bool
     * }
     */
    public static function get(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if ($userId === 0) {
            return self::$cache = self::guest();
        }

        // p.* rather than named profile columns on purpose: preferred_name only
        // exists after migration 007, and naming it here would make the whole
        // site fatal on an installation that hasn't run it yet. A missing column
        // is simply an absent array key.
        $stmt = Database::connection()->prepare(
            "SELECT u.id, u.email,
                    p.*,
                    k.status AS kyc_status,
                    (SELECT COUNT(*) FROM notifications n
                     WHERE n.user_id = u.id AND n.read_at IS NULL) AS unread
             FROM users u
             LEFT JOIN user_profiles p ON p.user_id = u.id
             LEFT JOIN user_kyc k ON k.user_id = u.id
             WHERE u.id = ?
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row) {
            // Session points at a user that no longer exists. Treat as signed
            // out rather than rendering a nav for a ghost.
            return self::$cache = self::guest();
        }

        $first = trim((string) ($row['first_name'] ?? ''));
        $last = trim((string) ($row['last_name'] ?? ''));
        $preferred = trim((string) ($row['preferred_name'] ?? ''));
        $fullName = trim($first . ' ' . $last);
        $kycStatus = $row['kyc_status'] ?? null;

        return self::$cache = [
            'signed_in' => true,
            'id'        => (int) $row['id'],
            'email'     => (string) $row['email'],
            // What the bar greets you by. Preferred name wins, then first name,
            // then the part of the email before the @ - never a blank space.
            'name'      => $preferred ?: ($first ?: strstr((string) $row['email'], '@', true) ?: 'Your account'),
            'full_name' => $fullName !== '' ? $fullName : (string) $row['email'],
            'initials'  => self::initials($first, $last, (string) $row['email']),
            'kyc_status'=> $kycStatus,
            'verified'  => $kycStatus === 'verified',
            'unread'    => (int) ($row['unread'] ?? 0),
            // Drives the dot on the avatar: something in the account needs the
            // person to do something, rather than something merely being unread.
            'needs_attention' => $kycStatus !== 'verified',
        ];
    }

    /** Test seam, and useful if a controller changes the session mid-request. */
    public static function reset(): void
    {
        self::$cache = null;
    }

    private static function guest(): array
    {
        return [
            'signed_in' => false,
            'id'        => 0,
            'email'     => '',
            'name'      => '',
            'full_name' => '',
            'initials'  => '',
            'kyc_status'=> null,
            'verified'  => false,
            'unread'    => 0,
            'needs_attention' => false,
        ];
    }

    /**
     * Two letters for the avatar, UTF-8 safe without needing mbstring - a host
     * without the extension shouldn't lose its navigation bar.
     */
    private static function initials(string $first, string $last, string $email): string
    {
        $letter = static function (string $value): string {
            $value = trim($value);
            return ($value !== '' && preg_match('/^./u', $value, $m)) ? strtoupper($m[0]) : '';
        };

        $initials = $letter($first) . $letter($last);

        return $initials !== '' ? $initials : strtoupper(substr($email, 0, 2));
    }
}
