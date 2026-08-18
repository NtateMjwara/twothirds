<?php
namespace app\services;

use app\core\Database;

class KycReviewService
{
    public static function approve(int $kycId, int $adminId): void
    {
        self::updateStatus($kycId, 'verified', $adminId, null, 'approve_kyc');
    }

    public static function reject(int $kycId, int $adminId, string $reason): void
    {
        self::updateStatus($kycId, 'rejected', $adminId, $reason, 'reject_kyc');
    }

    private static function updateStatus(int $kycId, string $status, int $adminId, ?string $reason, string $action): void
    {
        $db = Database::connection();
        $db->beginTransaction();

        $kyc = null;
        try {
            $stmt = $db->prepare("SELECT * FROM user_kyc WHERE id = ? FOR UPDATE");
            $stmt->execute([$kycId]);
            $kyc = $stmt->fetch();

            if (!$kyc) {
                throw new \RuntimeException('KYC record not found.');
            }

            $db->prepare(
                "UPDATE user_kyc SET status = ?, rejection_reason = ?, verified_by = ?, verified_at = NOW() WHERE id = ?"
            )->execute([$status, $reason, $adminId, $kycId]);

            $db->prepare(
                "INSERT INTO audit_log (actor_type, actor_id, action, entity_type, entity_id, details)
                 VALUES ('admin', ?, ?, 'user_kyc', ?, ?)"
            )->execute([$adminId, $action, $kycId, $reason ?? "Status set to {$status}"]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e; // exits here on failure - the notification below never fires
        }

        $message = $status === 'verified'
            ? 'Your identity verification was approved.'
            : 'Your identity verification was not approved' . ($reason ? ": {$reason}" : '.');

        NotificationService::notify(
            (int) $kyc['user_id'],
            'kyc_' . $status,
            $message,
            'Update on your identity verification',
            "<p>{$message}</p>"
        );
    }
}
