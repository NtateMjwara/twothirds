<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Company;
use app\services\CommitmentService;
use app\services\CompanyService;
use app\services\OfferWindow;

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

        if (!$this->offerIsOpen($company, $available)) {
            return;
        }

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

        $available = CompanyService::sharesAvailable((int) $company['id'], (int) $company['shares_issued']);

        // Checked again on the write, not just on the form. The window can close
        // between loading the page and submitting it, and a POST can arrive
        // without the GET ever happening.
        if (!$this->offerIsOpen($company, $available)) {
            return;
        }

        $shares = (int) ($_POST['shares'] ?? 0);

        try {
            $commitment = CommitmentService::create((int) $_SESSION['user_id'], (int) $company['id'], $shares);
        } catch (\InvalidArgumentException $e) {
            $this->render('commit/form', [
                'company'   => $company,
                'available' => CompanyService::sharesAvailable((int) $company['id'], (int) $company['shares_issued']),
                'error'     => $e->getMessage(),
            ]);
            return;
        }

        $this->render('commit/confirmation', ['reference' => $commitment['reference']]);
    }

    /**
     * The company page hides the commit button outside the offer window, but a
     * hidden button is not a control - /commit/{reference} is a URL anyone can
     * type. This is the check that actually enforces it.
     *
     * Redirects back to the company page on failure, where the invest card
     * already explains why committing isn't possible right now.
     *
     * @return bool true when the caller should carry on
     */
    private function offerIsOpen(array $company, int $available): bool
    {
        $offer = OfferWindow::for($company, $available);

        if ($offer['can_commit']) {
            return true;
        }

        $this->redirect('/company/' . $company['reference']);
        return false;
    }
}
