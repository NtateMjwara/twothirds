<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\models\Commitment;
use app\services\SettlementService;

class SettlementController extends AdminController
{
    public function index(): void
    {
        $this->requirePermission('settlement.view');

        $this->render('admin/settlement', [
            'queue'  => Commitment::pendingQueue(),
            'canAct' => $this->can('settlement.act'),
        ]);
    }

    public function confirm(string $id): void
    {
        $this->requirePermission('settlement.act');
        $this->verifyCsrf();

        try {
            SettlementService::confirm((int) $id, $this->adminId());
            $this->flash('success', 'Commitment settled. The shares are on the register and the investor has been notified.');
        } catch (\DomainException | \InvalidArgumentException $e) {
            $this->flash('warning', $e->getMessage());
        } catch (\Throwable $e) {
            // Settlement writes to the share register. A silent failure here
            // means an admin believes an investor owns shares they don't.
            error_log('Settlement confirm failed: ' . $e->getMessage());
            $this->flash('error', 'Settlement failed and nothing was written to the register. Do not re-run it until you have checked the commitment.');
        }

        $this->redirect('/admin/settlement');
    }

    public function cancel(string $id): void
    {
        $this->requirePermission('settlement.act');
        $this->verifyCsrf();

        try {
            SettlementService::cancel((int) $id, $this->adminId());
            $this->flash('success', 'Commitment cancelled and the shares released back to available.');
        } catch (\DomainException | \InvalidArgumentException $e) {
            $this->flash('warning', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('Settlement cancel failed: ' . $e->getMessage());
            $this->flash('error', 'The cancellation could not be saved. Nothing was changed.');
        }

        $this->redirect('/admin/settlement');
    }
}
