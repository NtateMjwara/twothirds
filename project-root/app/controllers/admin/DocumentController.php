<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\models\Company;
use app\services\CompanyDocumentService;

class DocumentController extends AdminController
{
    public function create(string $reference): void
    {
        $this->requireAdmin();
        $company = Company::findByReference($reference);
        if (!$company) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }
        $this->render('admin/documents/create', ['company' => $company]);
    }

    public function store(string $reference): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $company = Company::findByReference($reference);
        if (!$company) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $docType = trim($_POST['doc_type'] ?? '');

        if ($docType === '' || empty($_FILES['file']['tmp_name'])) {
            $this->render('admin/documents/create', ['company' => $company, 'error' => 'Document type and a file are both required.']);
            return;
        }

        try {
            CompanyDocumentService::upload((int) $company['id'], $docType, $_FILES['file']);
        } catch (\InvalidArgumentException $e) {
            $this->render('admin/documents/create', ['company' => $company, 'error' => $e->getMessage()]);
            return;
        }

        $this->redirect('/company/' . $company['reference']);
    }
}
