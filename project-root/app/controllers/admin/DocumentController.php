<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\models\Company;
use app\models\Document;
use app\services\CompanyDocumentService;

class DocumentController extends AdminController
{
    private const DOC_TYPES = [
        'financial_statements' => 'Financial statements',
        'valuation_certificate' => 'Valuation certificate',
        'moi' => 'Memorandum of incorporation',
        'vehicle_registration' => 'Vehicle registration',
        'insurance_certificate' => 'Insurance certificate',
        'roadworthy_certificate' => 'Roadworthy certificate',
        'operator_agreement' => 'Operator agreement',
    ];

    public function create(string $reference): void
    {
        $this->requirePermission('document.manage');
        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $this->renderForm($company);
    }

    public function store(string $reference): void
    {
        $this->requirePermission('document.manage');
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $docType = trim($_POST['doc_type'] ?? '');

        // Whitelist rather than trusting the select. doc_type is displayed on the
        // public company page, and the field is a plain VARCHAR.
        if (!isset(self::DOC_TYPES[$docType])) {
            $this->renderForm($company, 'Choose a document type from the list.');
            return;
        }

        try {
            CompanyDocumentService::upload((int) $company['id'], $docType, $_FILES['file'] ?? []);
        } catch (\InvalidArgumentException $e) {
            $this->renderForm($company, $e->getMessage());
            return;
        } catch (\Throwable $e) {
            error_log('Company document upload failed: ' . $e->getMessage());
            $this->renderForm($company, 'The file could not be saved. Nothing was uploaded.');
            return;
        }

        $this->audit('upload_document', 'companies', (int) $company['id'], 'Uploaded ' . self::DOC_TYPES[$docType]);
        $this->flash('success', self::DOC_TYPES[$docType] . ' uploaded.');

        // Stayed on the public company page before, which is the wrong place to
        // land when the next thing is usually another upload.
        $this->redirect('/admin/companies/' . $company['reference'] . '/documents');
    }

    private function renderForm(array $company, ?string $error = null): void
    {
        $this->render('admin/documents/create', [
            'company'   => $company,
            'docTypes'  => self::DOC_TYPES,
            'documents' => Document::forCompany((int) $company['id']),
            'error'     => $error,
        ]);
    }

    private function findCompanyOr404(string $reference): ?array
    {
        $company = Company::findByReference($reference);
        if (!$company) {
            http_response_code(404);
            $this->render('errors/404');
            return null;
        }
        return $company;
    }
}
