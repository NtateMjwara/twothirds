<?php
namespace app\services;

use app\models\Document;

class CompanyDocumentService
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    private const MAX_BYTES = 10 * 1024 * 1024; // 10MB - financial statements run larger than a KYC scan

    public static function upload(int $companyId, string $docType, array $file): void
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Only PDF, JPG or PNG files are accepted.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new \InvalidArgumentException('File must be under 10MB.');
        }

        $storageDir = __DIR__ . "/../storage/documents/company/{$companyId}";
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0750, true);
        }

        $filename = preg_replace('/[^a-z0-9_-]/i', '_', $docType) . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destination = $storageDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('Could not save the uploaded file.');
        }

        Document::create([
            'company_id' => $companyId,
            'doc_type'   => $docType,
            'file_path'  => "company/{$companyId}/{$filename}",
            'verified'   => 1, // admin-uploaded company documents are authoritative by definition
        ]);
    }
}
