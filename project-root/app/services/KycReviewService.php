<?php
namespace app\services;

use app\core\Database;

class KycReviewService
{
    public static function approve(int $kycId, int $adminId): array
    {
        return self::updateStatus($kycId, 'verified', $adminId, null, 'approve_kyc');
    }

    public static function reject(int $kycId, int $adminId, string $reason): array
    {
        return self::updateStatus($kycId, 'rejected', $adminId, $reason, 'reject_kyc');
    }

    /**
     * @return array the KYC row as it was before the change, so the caller can
     *               name the investor in a confirmation message.
     */
    private static function updateStatus(int $kycId, string $status, int $adminId, ?string $reason, string $action): array
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT * FROM user_kyc WHERE id = ? FOR UPDATE");
            $stmt->execute([$kycId]);
            $kyc = $stmt->fetch();

            if (!$kyc) {
                throw new \RuntimeException('That KYC submission no longer exists.');
            }

            // Only a pending submission can be decided.
            //
            // Without this, two admins working the queue at the same time both
            // succeed, the second silently overwrites verified_by and verified_at,
            // and the investor gets two notifications about the same document. The
            // row lock above serialises them; this check makes the loser fail
            // loudly instead of quietly winning.
            if ($kyc['status'] !== 'pending') {
                throw new \DomainException(
                    'That submission was already ' . $kyc['status'] . '. Reload the queue to see the current state.'
                );
            }

            $db->prepare(
                "UPDATE user_kyc
                 SET status = ?, rejection_reason = ?, verified_by = ?, verified_at = NOW()
                 WHERE id = ?"
            )->execute([$status, $reason, $adminId, $kycId]);

            // The uploaded ID scan is what was actually reviewed, so the decision
            // belongs on the document too. Leaving documents.verified at 0 after an
            // approval meant nothing downstream could tell a checked document from
            // an unchecked one.
            if ($kyc['document_id']) {
                $db->prepare("UPDATE documents SET verified = ? WHERE id = ?")
                   ->execute([$status === 'verified' ? 1 : 0, $kyc['document_id']]);
            }

            $db->prepare(
                "INSERT INTO audit_log (actor_type, actor_id, action, entity_type, entity_id, details)
                 VALUES ('admin', ?, ?, 'user_kyc', ?, ?)"
            )->execute([$adminId, $action, $kycId, $reason ?? "Status set to {$status}"]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $message = $status === 'verified'
            ? 'Your identity verification was approved.'
            : 'Your identity verification was not approved' . ($reason ? ": {$reason}" : '.');

        // The review is committed. If the notification fails - mail server down,
        // queue table locked - that must not be reported as a failed review, or
        // an admin will try again and hit the "already verified" guard above with
        // no idea what actually happened.
        try {
            NotificationService::notify(
                (int) $kyc['user_id'],
                'kyc_' . $status,
                $message,
                'Update on your identity verification',
                "<p>{$message}</p>"
            );
        } catch (\Throwable $e) {
            error_log("KYC {$status} recorded for #{$kycId} but the notification failed: " . $e->getMessage());
        }

        return $kyc;
    }
}
