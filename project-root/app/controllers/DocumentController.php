<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Document;

class DocumentController extends Controller
{
    public function download(string $id): void
    {
        $document = Document::find((int) $id);
        if (!$document) {
            http_response_code(404);
            exit('Not found.');
        }

        // Company documents are public by design - that's the transparency pitch the
        // whole company page is built around. Personal (KYC) documents stay private
        // to their owner and admins, checked the same way as before.
        if ($document['company_id'] === null) {
            $isOwner = !empty($_SESSION['user_id'])
                && $document['user_id'] !== null
                && (int) $document['user_id'] === (int) $_SESSION['user_id'];
            $isAdmin = !empty($_SESSION['admin_id']);

            if (!$isOwner && !$isAdmin) {
                http_response_code(403);
                exit('Forbidden.');
            }
        }

        $fullPath = realpath(__DIR__ . '/../storage/documents/' . $document['file_path']);
        $storageRoot = realpath(__DIR__ . '/../storage/documents');

        if (!$fullPath || !$storageRoot || strpos($fullPath, $storageRoot) !== 0 || !is_file($fullPath)) {
            http_response_code(404);
            exit('File missing.');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }
}
