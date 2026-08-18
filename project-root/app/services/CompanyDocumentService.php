<?php
namespace app\services;

use app\models\Document;

class CompanyDocumentService
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    // Mapped from the file's own bytes, not its name. An attacker controls the
    // extension; they don't control what finfo reads out of the header.
    private const ALLOWED_MIME = ['application/pdf', 'image/jpeg', 'image/png'];
    private const MAX_BYTES = 10 * 1024 * 1024; // financial statements run larger than a KYC scan

    public static function upload(int $companyId, string $docType, array $file): int
    {
        // PHP reports upload problems here, not by omitting the file. Checking
        // only for an empty tmp_name turned "your file was 40MB" into the
        // generic "a file is required", which is why oversized uploads looked
        // like the form was broken.
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException(self::describeUploadError($error));
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('That file did not arrive as an upload.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Only PDF, JPG or PNG files are accepted.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new \InvalidArgumentException('File must be under 10MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new \InvalidArgumentException(
                'That file is not a PDF or an image, whatever its name says. Detected type: ' . $mime . '.'
            );
        }

        $storageDir = __DIR__ . "/../storage/documents/company/{$companyId}";
        if (!is_dir($storageDir) && !mkdir($storageDir, 0750, true) && !is_dir($storageDir)) {
            throw new \RuntimeException('Could not create the storage directory.');
        }

        $filename = preg_replace('/[^a-z0-9_-]/i', '_', $docType) . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destination = $storageDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('Could not save the uploaded file.');
        }

        try {
            return (int) Document::create([
                'company_id' => $companyId,
                'doc_type'   => $docType,
                'file_path'  => "company/{$companyId}/{$filename}",
                'verified'   => 1, // admin-uploaded company documents are authoritative by definition
            ]);
        } catch (\Throwable $e) {
            // Don't leave a file on disk with no row pointing at it.
            @unlink($destination);
            throw $e;
        }
    }

    private static function describeUploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                'That file is larger than the server accepts. Check upload_max_filesize and post_max_size if it is under 10MB.',
            UPLOAD_ERR_PARTIAL   => 'The upload was interrupted. Try again.',
            UPLOAD_ERR_NO_FILE   => 'Choose a file to upload.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE =>
                'The server could not write the upload to disk. This is a server configuration problem, not your file.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension blocked the upload.',
            default              => 'The upload did not complete.',
        };
    }
}
