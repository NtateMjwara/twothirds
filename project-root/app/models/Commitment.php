<?php
namespace app\models;

use app\core\Model;
use app\core\Database;

class Commitment extends Model
{
    protected static string $table = 'commitments';

    // Everything the admin settlement queue needs, joined in one query.
    public static function pendingQueue(): array
    {
        $stmt = Database::connection()->query(
            "SELECT co.*, c.name AS company_name, c.reference AS company_reference,
                    u.email AS user_email, p.first_name, p.last_name
             FROM commitments co
             JOIN companies c ON c.id = co.company_id
             JOIN users u ON u.id = co.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             WHERE co.status = 'pending'
             ORDER BY co.created_at ASC"
        );
        return $stmt->fetchAll();
    }

    public static function pendingForUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT co.*, c.name AS company_name, c.reference AS company_reference, c.nav_per_share
             FROM commitments co
             JOIN companies c ON c.id = co.company_id
             WHERE co.user_id = ? AND co.status = 'pending'
             ORDER BY co.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
