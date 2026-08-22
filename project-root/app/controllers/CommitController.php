<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Company;
use app\models\CompanyBankAccount;
use app\models\Commitment;
use app\models\CommitmentAgreement;
use app\models\LegalDocument;
use app\models\User;
use app\models\UserKyc;
use app\models\UserProfile;
use app\services\CommitmentService;
use app\services\CompanyService;
use app\services\InvoiceService;
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

        if (!$this->canCommit($company, $available)) {
            return;
        }

        $this->renderForm($company, $available);
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

        // Re-checked on the write, not just on the form. The window can close,
        // KYC can be revoked, and the last shares can be taken between loading
        // the page and submitting it.
        if (!$this->canCommit($company, $available)) {
            return;
        }

        $documents = LegalDocument::forCommitment((int) $company['id']);
        $accepted = $_POST['agree'] ?? [];
        $accepted = is_array($accepted) ? $accepted : [];

        // Every required document must be ticked. Checked against the documents
        // the server decided to show, not against what the form posted back -
        // otherwise a submission that simply omits a checkbox name would pass.
        $missing = [];
        $agreedDocuments = [];

        foreach ($documents as $doc) {
            $ticked = in_array((string) $doc['id'], array_map('strval', $accepted), true);

            if ($ticked) {
                $agreedDocuments[] = $doc;
            } elseif ((int) $doc['is_required'] === 1) {
                $missing[] = $doc['title'];
            }
        }

        if ($missing) {
            $this->renderForm(
                $company,
                $available,
                'You need to accept ' . self::listOf($missing) . ' before committing.'
            );
            return;
        }

        $shares = (int) ($_POST['shares'] ?? 0);

        try {
            $commitment = CommitmentService::create((int) $_SESSION['user_id'], (int) $company['id'], $shares);
        } catch (\InvalidArgumentException $e) {
            $this->renderForm($company, $available, $e->getMessage());
            return;
        }

        $user = User::find((int) $_SESSION['user_id']);
        $profile = UserProfile::forUser((int) $_SESSION['user_id']);

        try {
            $invoice = InvoiceService::issue(
                $commitment,
                $company,
                array_merge($user, $profile ?? []),
                $agreedDocuments
            );
        } catch (\Throwable $e) {
            // The commitment exists and the shares are reserved. Losing the
            // invoice is recoverable; pretending the commitment failed is not,
            // because the investor would try again and reserve twice.
            error_log('Invoicing failed for commitment ' . $commitment['reference'] . ': ' . $e->getMessage());

            $this->render('commit/confirmation', [
                'reference' => $commitment['reference'],
                'invoice'   => null,
                'company'   => $company,
                'error'     => 'Your shares are reserved, but we could not generate the invoice. '
                             . 'Contact us quoting ' . $commitment['reference'] . ' and we will send it.',
            ]);
            return;
        }

        $this->render('commit/confirmation', [
            'reference' => $commitment['reference'],
            'invoice'   => $invoice,
            'company'   => $company,
            'error'     => null,
        ]);
    }

    // ------------------------------------------------------------

    /**
     * Every reason a commitment can't proceed, checked in one place.
     *
     * Renders or redirects and returns false when blocked, so both actions can
     * simply `if (!$this->canCommit(...)) return;`.
     */
    private function canCommit(array $company, int $available): bool
    {
        $offer = OfferWindow::for($company, $available);

        if (!$offer['can_commit']) {
            // The company page already explains why, in the invest card.
            $this->redirect(company_url($company));
            return false;
        }

        // Identity has to be verified before someone can subscribe for shares in
        // a company. This is the platform's FICA obligation, not a nicety.
        $kyc = UserKyc::forUser((int) $_SESSION['user_id']);

        if (!$kyc || $kyc['status'] !== 'verified') {
            $this->render('commit/blocked', [
                'company' => $company,
                'reason'  => 'kyc',
                'status'  => $kyc['status'] ?? null,
            ]);
            return false;
        }

        // No bank account on the SPV means the invoice would tell the investor
        // to pay and give them nowhere to pay it.
        if (!CompanyBankAccount::isComplete(CompanyBankAccount::forCompany((int) $company['id']))) {
            error_log("Commitment blocked: {$company['reference']} has no banking details.");

            $this->render('commit/blocked', [
                'company' => $company,
                'reason'  => 'no_bank_account',
                'status'  => null,
            ]);
            return false;
        }

        return true;
    }

    private function renderForm(array $company, int $available, ?string $error = null): void
    {
        $this->render('commit/form', [
            'company'   => $company,
            'available' => $available,
            'documents' => LegalDocument::forCommitment((int) $company['id']),
            'feeRate'   => InvoiceService::TRANSACTION_FEE_RATE,
            'error'     => $error,
            'old'       => $error !== null ? $_POST : [],
        ]);
    }

    /** "the MOI, the Risk Disclosure and the Subscription Agreement" */
    private static function listOf(array $items): string
    {
        if (count($items) === 1) {
            return 'the ' . $items[0];
        }

        $last = array_pop($items);

        return 'the ' . implode(', the ', $items) . ' and the ' . $last;
    }
}
