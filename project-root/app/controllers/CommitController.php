<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Company;
use app\services\CommitmentService;
use app\services\CompanyService;

class CommitController extends Controller
{
    public function show(string $reference): void
    {
        $this->requireAuth();

        $company = Company::findByReference($reference);
        if (!$company) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $available = CompanyService::sharesAvailable((int) $company['id'], (int) $company['shares_issued']);
        $this->render('commit/form', ['company' => $company, 'available' => $available]);
    }

    public function store(string $reference): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $company = Company::findByReference($reference);
        if (!$company) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $shares = (int) ($_POST['shares'] ?? 0);

        try {
            $commitment = CommitmentService::create((int) $_SESSION['user_id'], (int) $company['id'], $shares);
        } catch (\InvalidArgumentException $e) {
            $available = CompanyService::sharesAvailable((int) $company['id'], (int) $company['shares_issued']);
            $this->render('commit/form', ['company' => $company, 'available' => $available, 'error' => $e->getMessage()]);
            return;
        }

        $this->render('commit/confirmation', ['reference' => $commitment['reference']]);
    }
}
