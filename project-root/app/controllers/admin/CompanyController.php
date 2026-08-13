<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\core\Database;
use app\models\Company;
use app\models\Asset;
use app\models\ActivityType;
use app\models\AssetClass;
use app\models\CommercialActivity;
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
        $this->render('admin/companies/create', $this->taxonomy());
    }

    public function store(): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $companyData = [
            'name'                 => trim($_POST['name'] ?? ''),
            'registration_number'  => trim($_POST['registration_number'] ?? '') ?: null,
            'incorporation_date'   => ($_POST['incorporation_date'] ?? '') ?: null,
            'registered_address'   => trim($_POST['registered_address'] ?? ''),
            'corporate_secretary'  => 'Platform',
            'shares_issued'        => (int) ($_POST['shares_issued'] ?? 0),
            'nav_per_share'        => (float) ($_POST['nav_per_share'] ?? 1.00),
        ];

        $assetClassId   = (int) ($_POST['asset_class_id'] ?? 0) ?: null;
        $activityTypeId = (int) ($_POST['activity_type_id'] ?? 0) ?: null;

        $assetData = [
            'asset_class_id'       => $assetClassId,
            'make'                 => trim($_POST['make'] ?? ''),
            'model'                => trim($_POST['model'] ?? ''),
            'year'                 => (int) ($_POST['year'] ?? 0) ?: null,
            'vin'                  => trim($_POST['vin'] ?? ''),
            'registration_number'  => trim($_POST['asset_registration_number'] ?? ''),
            'purchase_price'       => (float) ($_POST['purchase_price'] ?? 0),
            'purchase_date'        => ($_POST['purchase_date'] ?? '') ?: null,
            'current_valuation'    => (float) ($_POST['current_valuation'] ?? 0),
            'valuation_date'       => ($_POST['valuation_date'] ?? '') ?: null,
            'mileage'              => (int) ($_POST['mileage'] ?? 0),
            'asset_status'         => 'active',
        ];

        $activityData = [
            'activity_type_id' => $activityTypeId,
            // The legacy varchar is kept in sync rather than abandoned, so anything
            // still reading it - homepage cards, older exports - stays truthful.
            'activity_type'    => ActivityType::labelFor($activityTypeId),
            'operator'         => trim($_POST['operator'] ?? ''),
            'location'         => trim($_POST['location'] ?? ''),
            'utilisation_rate' => $this->utilisationOrNull(),
            'start_date'       => ($_POST['activity_start_date'] ?? '') ?: date('Y-m-d'),
        ];

        // Classification is required at creation. An SPV with no activity and no
        // asset class is invisible to every filter on the discovery page, which is
        // a worse outcome than making the admin pick from a list.
        if ($companyData['name'] === '' || $assetData['vin'] === '' || !$assetClassId || !$activityTypeId) {
            $this->render('admin/companies/create', [
                'error' => 'Company name, VIN, asset class and commercial activity are all required.',
            ] + $this->taxonomy());
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
            'company'  => $company,
            'asset'    => $assets[0] ?? null,
            'activity' => CommercialActivity::latestForCompany((int) $company['id']),
        ] + $this->taxonomy());
    }

    public function update(string $reference): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $assets = Asset::where('company_id', $company['id']);
        $asset = $assets[0] ?? null;
        $activity = CommercialActivity::latestForCompany((int) $company['id']);

        $companyData = [
            'name'                => trim($_POST['name'] ?? ''),
            'registration_number' => trim($_POST['registration_number'] ?? '') ?: null,
            'incorporation_date'  => ($_POST['incorporation_date'] ?? '') ?: null,
            'registered_address'  => trim($_POST['registered_address'] ?? ''),
            'corporate_secretary' => trim($_POST['corporate_secretary'] ?? '') ?: 'Platform',
            'status'              => in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active',
            'shares_issued'       => (int) ($_POST['shares_issued'] ?? 0),
            'nav_per_share'       => (float) ($_POST['nav_per_share'] ?? 1.00),
        ];

        if ($companyData['name'] === '') {
            $this->render('admin/companies/edit', [
                'company'  => $company,
                'asset'    => $asset,
                'activity' => $activity,
                'error'    => 'Company name is required.',
            ] + $this->taxonomy());
            return;
        }

        Company::update((int) $company['id'], $companyData);

        if ($asset) {
            Asset::update((int) $asset['id'], [
                'asset_class_id'      => (int) ($_POST['asset_class_id'] ?? 0) ?: null,
                'make'                => trim($_POST['make'] ?? ''),
                'model'               => trim($_POST['model'] ?? ''),
                'year'                => (int) ($_POST['year'] ?? 0) ?: null,
                'vin'                 => trim($_POST['vin'] ?? ''),
                'registration_number' => trim($_POST['asset_registration_number'] ?? ''),
                'current_valuation'   => (float) ($_POST['current_valuation'] ?? 0),
                'valuation_date'      => ($_POST['valuation_date'] ?? '') ?: null,
                'mileage'             => (int) ($_POST['mileage'] ?? 0),
                'insurance_status'    => trim($_POST['insurance_status'] ?? ''),
                'roadworthy_status'   => trim($_POST['roadworthy_status'] ?? ''),
                'asset_status'        => in_array($_POST['asset_status'] ?? '', ['active', 'inactive', 'sold'], true) ? $_POST['asset_status'] : 'active',
            ]);
        }

        $activityChanged = $this->syncCommercialActivity((int) $company['id'], $activity);

        $details = "Edited {$companyData['name']} ({$company['reference']})";
        if ($activityChanged) {
            $details .= ' - commercial activity changed, previous period closed';
        }

        Database::connection()->prepare(
            "INSERT INTO audit_log (actor_type, actor_id, action, entity_type, entity_id, details)
             VALUES ('admin', ?, 'edit_company', 'companies', ?, ?)"
        )->execute([$_SESSION['admin_id'], $company['id'], $details]);

        $this->redirect('/company/' . $company['reference']);
    }

    /**
     * Applies the submitted commercial activity to the company.
     *
     * `commercial_activities` is a history, not a settings row: each entry is a
     * period the asset spent doing one kind of work. So switching a minibus from
     * a taxi route to staff transport closes the current period and opens a new
     * one, while correcting the operator, area or utilisation edits the current
     * period in place. Without that distinction, changing the activity would
     * silently rewrite what the company was doing last year.
     *
     * @return bool whether the activity itself changed (as opposed to its details)
     */
    private function syncCommercialActivity(int $companyId, ?array $current): bool
    {
        $activityTypeId = (int) ($_POST['activity_type_id'] ?? 0) ?: null;

        $fields = [
            'operator'         => trim($_POST['operator'] ?? ''),
            'location'         => trim($_POST['location'] ?? ''),
            'utilisation_rate' => $this->utilisationOrNull(),
        ];

        if (!$current) {
            if ($activityTypeId) {
                $this->openActivityPeriod($companyId, $activityTypeId, $fields);
                return true;
            }
            return false;
        }

        if ((int) $current['activity_type_id'] === (int) $activityTypeId) {
            CommercialActivity::update((int) $current['id'], $fields);
            return false;
        }

        CommercialActivity::update((int) $current['id'], ['end_date' => date('Y-m-d')]);

        if ($activityTypeId) {
            $this->openActivityPeriod($companyId, $activityTypeId, $fields);
        }

        return true;
    }

    private function openActivityPeriod(int $companyId, int $activityTypeId, array $fields): void
    {
        CommercialActivity::create($fields + [
            'company_id'       => $companyId,
            'activity_type_id' => $activityTypeId,
            'activity_type'    => ActivityType::labelFor($activityTypeId),
            'start_date'       => date('Y-m-d'),
        ]);
    }

    // An empty utilisation field means "not measured", which is different from
    // "measured at zero" - so it has to stay NULL rather than cast to 0.0.
    private function utilisationOrNull(): ?float
    {
        $value = $_POST['utilisation_rate'] ?? '';
        return ($value === '' || !is_numeric($value)) ? null : (float) $value;
    }

    // Both admin forms need the full picking lists, including on a failed submit.
    private function taxonomy(): array
    {
        return [
            'activityTypes' => ActivityType::groupedBySector(),
            'assetClasses'  => AssetClass::groupedByFamily(),
        ];
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
