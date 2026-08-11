<?php
namespace app\services;

use app\core\Database;
use app\models\Document;
use app\models\UserKyc;

class KycService
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    private const MAX_BYTES = 5 * 1024 * 1024; // 5MB

    public static function submit(int $userId, string $idType, string $idNumber, array $file): void
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Only PDF, JPG or PNG files are accepted.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new \InvalidArgumentException('File must be under 5MB.');
        }

        $storageDir = __DIR__ . '/../storage/documents/kyc';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0750, true);
        }

        // Random filename - never trust or reuse the name the browser sent
        $filename = 'kyc_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $storageDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('Could not save the uploaded file.');
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $documentId = Document::create([
                'user_id'   => $userId,
                'doc_type'  => 'kyc_id_document',
                'file_path' => 'kyc/' . $filename, // relative to app/storage/documents
                'verified'  => 0,
            ]);

            $existing = UserKyc::forUser($userId);
            if ($existing) {
                $db->prepare(
                    "UPDATE user_kyc
                     SET id_type = ?, id_number = ?, document_id = ?, status = 'pending',
                         verified_by = NULL, verified_at = NULL
                     WHERE user_id = ?"
                )->execute([$idType, $idNumber, $documentId, $userId]);
            } else {
                UserKyc::create([
                    'user_id'     => $userId,
                    'id_type'     => $idType,
                    'id_number'   => $idNumber,
                    'document_id' => $documentId,
                    'status'      => 'pending',
                ]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            @unlink($destination); // don't leave an orphaned file if the DB write failed
            throw $e;
        }
    }
}
