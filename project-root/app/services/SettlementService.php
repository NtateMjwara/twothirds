<?php
namespace app\services;

use app\core\Database;

class SettlementService
{
    public static function confirm(int $commitmentId, int $adminId): void
    {
        $db = Database::connection();
        $db->beginTransaction();

        $commitment = null;
        try {
            $stmt = $db->prepare("SELECT * FROM commitments WHERE id = ? AND status = 'pending' FOR UPDATE");
            $stmt->execute([$commitmentId]);
            $commitment = $stmt->fetch();

            if (!$commitment) {
                throw new \RuntimeException('Commitment not found, or already settled.');
            }

            $db->prepare("UPDATE commitments SET status = 'confirmed' WHERE id = ?")
               ->execute([$commitmentId]);

            $db->prepare(
                "INSERT INTO shareholdings (user_id, company_id, shares, commitment_id, settled_by)
                 VALUES (?, ?, ?, ?, ?)"
            )->execute([
                $commitment['user_id'],
                $commitment['company_id'],
                $commitment['shares_requested'],
                $commitment['id'],
                $adminId,
            ]);

            $db->prepare(
                "INSERT INTO audit_log (actor_type, actor_id, action, entity_type, entity_id, details)
                 VALUES ('admin', ?, 'confirm_settlement', 'commitments', ?, ?)"
            )->execute([$adminId, $commitmentId, "Settled {$commitment['shares_requested']} shares"]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e; // exits here on failure - the notification below never fires
        }

        $message = "Your commitment of {$commitment['shares_requested']} shares has been settled and added to your portfolio.";

        NotificationService::notify(
            (int) $commitment['user_id'],
            'settlement_confirmed',
            $message,
            'Your investment has been settled',
            "<p>{$message}</p>"
        );
    }

    // For when a commitment shouldn't proceed (investor backed out, payment never
    // arrived, etc.) - releases the shares immediately rather than waiting out the
    // full 14-day expiry window.
    public static function cancel(int $commitmentId, int $adminId): void
    {
        $db = Database::connection();
        $db->beginTransaction();

        $commitment = null;
        try {
            $stmt = $db->prepare("SELECT * FROM commitments WHERE id = ? AND status = 'pending' FOR UPDATE");
            $stmt->execute([$commitmentId]);
            $commitment = $stmt->fetch();

            if (!$commitment) {
                throw new \RuntimeException('Commitment not found, or already processed.');
            }

            $db->prepare("UPDATE commitments SET status = 'withdrawn' WHERE id = ?")
               ->execute([$commitmentId]);

            $db->prepare(
                "INSERT INTO audit_log (actor_type, actor_id, action, entity_type, entity_id, details)
                 VALUES ('admin', ?, 'cancel_commitment', 'commitments', ?, ?)"
            )->execute([$adminId, $commitmentId, "Cancelled {$commitment['shares_requested']} shares"]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e; // exits here on failure - the notification below never fires
        }

        $message = "Your commitment of {$commitment['shares_requested']} shares was cancelled by the platform.";

        NotificationService::notify(
            (int) $commitment['user_id'],
            'commitment_cancelled',
            $message,
            'Your commitment was cancelled',
            "<p>{$message}</p>"
        );
    }
}
