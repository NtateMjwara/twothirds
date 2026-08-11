<?php
namespace app\services;

use app\core\Database;

class CommitmentExpiryService
{
    public static function expireOverdue(): int
    {
        $db = Database::connection();

        $stmt = $db->prepare(
            "SELECT id, user_id, shares_requested, reference
             FROM commitments
             WHERE status = 'pending' AND expires_at < NOW()"
        );
        $stmt->execute();
        $overdue = $stmt->fetchAll();

        foreach ($overdue as $commitment) {
            // Flipping status is all that's needed - sharesAvailable() already
            // only counts commitments WHERE status = 'pending', so this alone
            // releases the shares back without touching anywhere else.
            $db->prepare("UPDATE commitments SET status = 'expired' WHERE id = ?")
               ->execute([$commitment['id']]);

            $message = "Your commitment {$commitment['reference']} for " .
                (int) $commitment['shares_requested'] . " shares has expired without being settled.";

            NotificationService::notify(
                (int) $commitment['user_id'],
                'commitment_expired',
                $message,
                'Your commitment has expired',
                "<p>{$message}</p>"
            );
        }

        return count($overdue);
    }
}
