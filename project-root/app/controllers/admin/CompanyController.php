<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\core\Database;
use app\models\Company;
use app\models\Asset;
use app\services\CompanyService;

class CompanyController extends AdminController
{
    public function index(): void
    {
        $this->requireAdmin();
        $companies = Company::all();
        $this->render('admin/companies/index', ['companies' => $companies]);
    }

    public function create(): void
    {
        $this->requireAdmin();
        $this->render('admin/companies/create');
    }

    public function store(): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $companyData = [
            'name'                 => trim($_POST['name'] ?? ''),
            'registration_number'  => trim($_POST['registration_number'] ?? '') ?: null,
            'incorporation_date'   => $_POST['incorporation_date'] ?: null,
            'registered_address'   => trim($_POST['registered_address'] ?? ''),
            'corporate_secretary'  => 'Platform',
            'shares_issued'        => (int) ($_POST['shares_issued'] ?? 0),
            'nav_per_share'        => (float) ($_POST['nav_per_share'] ?? 1.00),
        ];

        $assetData = [
            'make'                 => trim($_POST['make'] ?? ''),
            'model'                => trim($_POST['model'] ?? ''),
            'year'                 => (int) ($_POST['year'] ?? 0) ?: null,
            'vin'                  => trim($_POST['vin'] ?? ''),
            'registration_number'  => trim($_POST['asset_registration_number'] ?? ''),
            'purchase_price'       => (float) ($_POST['purchase_price'] ?? 0),
            'purchase_date'        => $_POST['purchase_date'] ?: null,
            'current_valuation'    => (float) ($_POST['current_valuation'] ?? 0),
            'valuation_date'       => $_POST['valuation_date'] ?: null,
            'mileage'              => (int) ($_POST['mileage'] ?? 0),
            'asset_status'         => 'active',
        ];

        $activityData = [
            'activity_type' => trim($_POST['activity_type'] ?? ''),
            'operator'      => trim($_POST['operator'] ?? ''),
            'location'      => trim($_POST['location'] ?? ''),
            'start_date'    => $_POST['activity_start_date'] ?: date('Y-m-d'),
        ];

        if ($companyData['name'] === '' || $assetData['vin'] === '') {
            $this->render('admin/companies/create', ['error' => 'Company name and VIN are required.']);
            return;
        }

        $company = CompanyService::createSpv($companyData, $assetData, $activityData, $_SESSION['admin_id']);

        $this->redirect('/admin/companies?created=' . urlencode($company['reference']));
    }

    public function edit(string $reference): void
    {
        $this->requireAdmin();
        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $assets = Asset::where('company_id', $company['id']);

        $this->render('admin/companies/edit', [
            'company' => $company,
            'asset'   => $assets[0] ?? null,
        ]);
    }

    public function update(string $reference): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $assets = Asset::where('company_id', $company['id']);
        $asset = $assets[0] ?? null;

        $companyData = [
            'name'                => trim($_POST['name'] ?? ''),
            'registration_number' => trim($_POST['registration_number'] ?? '') ?: null,
            'incorporation_date'  => $_POST['incorporation_date'] ?: null,
            'registered_address'  => trim($_POST['registered_address'] ?? ''),
            'corporate_secretary' => trim($_POST['corporate_secretary'] ?? '') ?: 'Platform',
            'status'              => in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active',
            'shares_issued'       => (int) ($_POST['shares_issued'] ?? 0),
            'nav_per_share'       => (float) ($_POST['nav_per_share'] ?? 1.00),
        ];

        if ($companyData['name'] === '') {
            $this->render('admin/companies/edit', ['company' => $company, 'asset' => $asset, 'error' => 'Company name is required.']);
            return;
        }

        Company::update((int) $company['id'], $companyData);

        if ($asset) {
            Asset::update((int) $asset['id'], [
                'make'                => trim($_POST['make'] ?? ''),
                'model'               => trim($_POST['model'] ?? ''),
                'year'                => (int) ($_POST['year'] ?? 0) ?: null,
                'vin'                 => trim($_POST['vin'] ?? ''),
                'registration_number' => trim($_POST['asset_registration_number'] ?? ''),
                'current_valuation'   => (float) ($_POST['current_valuation'] ?? 0),
                'valuation_date'      => $_POST['valuation_date'] ?: null,
                'mileage'             => (int) ($_POST['mileage'] ?? 0),
                'insurance_status'    => trim($_POST['insurance_status'] ?? ''),
                'roadworthy_status'   => trim($_POST['roadworthy_status'] ?? ''),
                'asset_status'        => in_array($_POST['asset_status'] ?? '', ['active', 'inactive', 'sold'], true) ? $_POST['asset_status'] : 'active',
            ]);
        }

        Database::connection()->prepare(
            "INSERT INTO audit_log (actor_type, actor_id, action, entity_type, entity_id, details)
             VALUES ('admin', ?, 'edit_company', 'companies', ?, ?)"
        )->execute([$_SESSION['admin_id'], $company['id'], "Edited {$companyData['name']} ({$company['reference']})"]);

        $this->redirect('/company/' . $company['reference']);
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
