<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Company;
use app\models\LegalDocument;

class LegalController extends Controller
{
    /**
     * /legal/{key} — read a document before accepting it.
     *
     * Public. An investor has to be able to read the terms before signing up,
     * and a document that can only be seen from inside the commit flow is a
     * document nobody reads.
     */
    public function show(string $key): void
    {
        // A company reference narrows to that SPV's own version where one
        // exists, which is how a company-specific MOI is reached.
        $companyId = null;
        $company = null;

        $reference = trim((string) ($_GET['company'] ?? ''));
        if ($reference !== '') {
            $company = Company::findByReference($reference);
            $companyId = $company ? (int) $company['id'] : null;
        }

        $document = LegalDocument::findActive($key, $companyId);

        if (!$document) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $this->render('pages/legal', [
            'document' => $document,
            'company'  => $company,
            'related'  => LegalDocument::forCommitment((int) $companyId),
        ]);
    }
}
