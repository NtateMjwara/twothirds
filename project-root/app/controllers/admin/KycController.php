<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\models\UserKyc;
use app\services\KycReviewService;

class KycController extends AdminController
{
    public function index(): void
    {
        $this->requirePermission('kyc.view');

        $this->render('admin/kyc', [
            'queue'    => UserKyc::pendingQueue(),
            'revealed' => (int) ($_SESSION['_kyc_revealed'] ?? 0),
            'canAct'   => $this->can('kyc.act'),
            'canReveal'=> $this->can('kyc.reveal'),
        ]);

        // One reveal at a time, and only for the render immediately after the
        // request that asked for it. Refreshing the queue re-masks everything.
        unset($_SESSION['_kyc_revealed']);
    }

    public function approve(string $id): void
    {
        $this->requirePermission('kyc.act');
        $this->verifyCsrf();

        try {
            $kyc = KycReviewService::approve((int) $id, $this->adminId());
            $this->flash('success', 'Identity verification approved for investor #' . (int) $kyc['user_id'] . '.');
        } catch (\DomainException $e) {
            // Expected conflict - say exactly what happened.
            $this->flash('warning', $e->getMessage());
        } catch (\Throwable $e) {
            // The old code logged this and redirected as though it had worked.
            // A compliance decision that silently fails is worse than one that
            // errors, because nobody goes back to check.
            error_log('KYC approve failed: ' . $e->getMessage());
            $this->flash('error', 'The approval could not be saved. Nothing was changed - try again, and if it keeps failing send the time to whoever runs the server.');
        }

        $this->redirect('/admin/kyc');
    }

    public function reject(string $id): void
    {
        $this->requirePermission('kyc.act');
        $this->verifyCsrf();

        $reason = trim($_POST['reason'] ?? '');

        // A rejection with no reason gives the investor nothing to fix, and the
        // notification reads as a dead end. The old default of "Not specified"
        // made that the easy path.
        if ($reason === '') {
            $this->flash('error', 'A rejection needs a reason - the investor is shown it and has to know what to correct.');
            $this->redirect('/admin/kyc');
            return;
        }

        try {
            $kyc = KycReviewService::reject((int) $id, $this->adminId(), mb_substr($reason, 0, 255));
            $this->flash('success', 'Verification rejected for investor #' . (int) $kyc['user_id'] . '. They have been notified with your reason.');
        } catch (\DomainException $e) {
            $this->flash('warning', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('KYC reject failed: ' . $e->getMessage());
            $this->flash('error', 'The rejection could not be saved. Nothing was changed.');
        }

        $this->redirect('/admin/kyc');
    }

    /**
     * Show one full ID number.
     *
     * The queue used to print every pending investor's ID number in the table.
     * A South African ID number carries date of birth, sex and citizenship, and
     * a screenshot or a shoulder-glance leaked the lot for everyone in the
     * queue at once. It is now masked by default, revealed one row at a time,
     * and every reveal is written to the audit log - so a support person
     * checking one document leaves a different trace from someone paging
     * through the queue copying numbers.
     */
    public function reveal(string $id): void
    {
        $this->requirePermission('kyc.reveal');
        $this->verifyCsrf();

        $kycId = (int) $id;
        $kyc = UserKyc::find($kycId);

        if ($kyc) {
            $this->audit('reveal_kyc_id_number', 'user_kyc', $kycId, 'Full ID number displayed in the review queue');
            $_SESSION['_kyc_revealed'] = $kycId;
        }

        $this->redirect('/admin/kyc');
    }
}
