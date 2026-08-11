<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\models\Company;
use app\services\FinancialPeriodService;

class FinancialPeriodController extends AdminController
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
        $this->render('admin/financial-periods/create', ['company' => $company]);
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

        $data = [
            'period_start'         => $_POST['period_start'] ?? null,
            'period_end'           => $_POST['period_end'] ?? null,
            'gross_revenue'        => (float) ($_POST['gross_revenue'] ?? 0),
            'operating_costs'      => (float) ($_POST['operating_costs'] ?? 0),
            'net_operating_income' => (float) ($_POST['net_operating_income'] ?? 0),
            'arf_allocation'       => (float) ($_POST['arf_allocation'] ?? 0),
        ];

        if (!$data['period_start'] || !$data['period_end']) {
            $this->render('admin/financial-periods/create', ['company' => $company, 'error' => 'Period start and end dates are required.']);
            return;
        }

        FinancialPeriodService::record((int) $company['id'], $data, (int) $_SESSION['admin_id']);

        $this->redirect('/company/' . $company['reference']);
    }
}
