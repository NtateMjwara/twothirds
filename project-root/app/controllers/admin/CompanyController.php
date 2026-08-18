<?php
namespace app\controllers\admin;

use app\core\AdminController;
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
        $this->requirePermission('company.view');

        $this->render('admin/companies/index', [
            'companies' => Company::all(),
            'canManage' => $this->can('company.manage'),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('company.manage');
        $this->render('admin/companies/create', $this->taxonomy());
    }

    public function store(): void
    {
        $this->requirePermission('company.manage');
        $this->verifyCsrf();

        $shares = (int) ($_POST['shares_issued'] ?? 0);

        // The asset's value at listing, and where it comes from. Current
        // valuation wins; purchase price is the fallback for a unit that hasn't
        // been valued yet.
        $valuation = (float) ($_POST['current_valuation'] ?? 0);
        $purchasePrice = (float) ($_POST['purchase_price'] ?? 0);
        $assetValue = $valuation > 0 ? $valuation : $purchasePrice;

        // NAV per share is derived here and nowhere else.
        //
        // The form shows it live, but the field is read-only and its value is
        // never read back - a browser can send whatever it likes. Every
        // percentage on the platform is built on this number (funding target,
        // progress bar, trailing yield), so it has to come from the two figures
        // that define it rather than from a box someone can mistype.
        $navPerShare = ($assetValue > 0 && $shares > 0)
            ? round($assetValue / $shares, 4)
            : 0.0;

        // VIN is optional. Empty must be stored as NULL, not '' - the column is
        // UNIQUE, and MySQL permits many NULLs but only one empty string, so a
        // second VIN-less SPV would collide with the first.
        $vin = strtoupper(trim($_POST['vin'] ?? ''));

        $companyData = [
            'name'                => trim($_POST['name'] ?? ''),
            'registration_number' => trim($_POST['registration_number'] ?? '') ?: null,
            'incorporation_date'  => ($_POST['incorporation_date'] ?? '') ?: null,
            'registered_address'  => trim($_POST['registered_address'] ?? ''),
            'corporate_secretary' => 'Platform',
            'shares_issued'       => $shares,
            'nav_per_share'       => $navPerShare,
        ];

        $assetClassId   = (int) ($_POST['asset_class_id'] ?? 0) ?: null;
        $activityTypeId = (int) ($_POST['activity_type_id'] ?? 0) ?: null;

        $assetData = [
            'asset_class_id'      => $assetClassId,
            'make'                => trim($_POST['make'] ?? ''),
            'model'               => trim($_POST['model'] ?? ''),
            'year'                => (int) ($_POST['year'] ?? 0) ?: null,
            'vin'                 => $vin !== '' ? $vin : null,
            'registration_number' => trim($_POST['asset_registration_number'] ?? ''),
            'purchase_price'      => $purchasePrice,
            'purchase_date'       => ($_POST['purchase_date'] ?? '') ?: null,
            'current_valuation'   => $valuation,
            'valuation_date'      => ($_POST['valuation_date'] ?? '') ?: null,
            'mileage'             => (int) ($_POST['mileage'] ?? 0),
            'asset_status'        => 'active',
        ];

        $activityData = [
            'activity_type_id' => $activityTypeId,
            // The legacy varchar is kept in sync rather than abandoned, so
            // anything still reading it stays truthful.
            'activity_type'    => ActivityType::labelFor($activityTypeId),
            'operator'         => trim($_POST['operator'] ?? ''),
            'location'         => trim($_POST['location'] ?? ''),
            'utilisation_rate' => $this->utilisationOrNull(),
            'start_date'       => ($_POST['activity_start_date'] ?? '') ?: date('Y-m-d'),
        ];

        [$error, $errorStep] = $this->validateNewSpv($companyData, $assetData, $assetClassId, $activityTypeId, $assetValue);

        if ($error === null) {
            try {
                $company = CompanyService::createSpv($companyData, $assetData, $activityData, $this->adminId());
                $this->flash(
                    'success',
                    $company['reference'] . ' created at R' . number_format($navPerShare, 2)
                        . ' a share. Add photographs and documents before it goes live.'
                );
                $this->redirect('/admin/companies/' . $company['reference'] . '/edit');
                return;
            } catch (\PDOException $e) {
                // VIN and company registration number are both UNIQUE. A duplicate
                // used to throw straight out of the controller as an uncaught
                // exception - a white screen, and the whole form lost.
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $isVin = stripos($e->getMessage(), 'vin') !== false;
                    $error = $isVin
                        ? 'That VIN is already registered to another company. A vehicle can only belong to one SPV.'
                        : 'Those details are already on the platform. A company registration number can only be used once.';
                    $errorStep = $isVin ? 2 : 1;
                } else {
                    $error = 'The SPV could not be created. Nothing was saved.';
                    $errorStep = 1;
                }
                error_log('SPV creation failed: ' . $e->getMessage());
            } catch (\Throwable $e) {
                $error = 'The SPV could not be created. Nothing was saved.';
                $errorStep = 1;
                error_log('SPV creation failed: ' . $e->getMessage());
            }
        }

        // Hand the submission back so a failed create doesn't wipe four steps of
        // typing, and reopen on the step that actually has the problem.
        $this->render('admin/companies/create', [
            'error'     => $error,
            'errorStep' => $errorStep,
            'old'       => $_POST,
        ] + $this->taxonomy());
    }

    public function edit(string $reference): void
    {
        $this->requirePermission('company.manage');
        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $this->renderEdit($company);
    }

    public function update(string $reference): void
    {
        $this->requirePermission('company.manage');
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $companyId = (int) $company['id'];
        $assets = Asset::where('company_id', $companyId);
        $asset = $assets[0] ?? null;
        $activity = CommercialActivity::latestForCompany($companyId);

        $opensAt  = $this->dateTimeOrNull($_POST['offer_opens_at'] ?? '');
        $closesAt = $this->dateTimeOrNull($_POST['offer_closes_at'] ?? '');
        $shares = (int) ($_POST['shares_issued'] ?? 0);

        // NAV is derived on edit exactly as it is on create: the submitted
        // valuation over the submitted share count, falling back to the purchase
        // price already on record. The field is read-only and unnamed, so
        // nothing comes back from the browser to trust.
        //
        // Where there is no asset there is nothing to value, so the stored NAV
        // is left alone rather than being driven to zero.
        $valuation = $asset !== null ? (float) ($_POST['current_valuation'] ?? 0) : 0.0;
        $purchasePrice = (float) ($asset['purchase_price'] ?? 0);
        $assetValue = $valuation > 0 ? $valuation : $purchasePrice;

        $navPerShare = ($asset !== null && $assetValue > 0 && $shares > 0)
            ? round($assetValue / $shares, 4)
            : (float) $company['nav_per_share'];

        $vin = strtoupper(trim($_POST['vin'] ?? ''));

        $companyData = [
            'name'                => trim($_POST['name'] ?? ''),
            'registration_number' => trim($_POST['registration_number'] ?? '') ?: null,
            'incorporation_date'  => ($_POST['incorporation_date'] ?? '') ?: null,
            'registered_address'  => trim($_POST['registered_address'] ?? ''),
            'corporate_secretary' => trim($_POST['corporate_secretary'] ?? '') ?: 'Platform',
            'status'              => in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active',
            'shares_issued'       => $shares,
            'nav_per_share'       => $navPerShare,
            'summary'             => trim($_POST['summary'] ?? '') ?: null,
            'investment_case'     => trim($_POST['investment_case'] ?? '') ?: null,
            'offer_opens_at'      => $opensAt,
            'offer_closes_at'     => $closesAt,
        ];

        // Shares issued cannot drop below what is already spoken for.
        //
        // sharesAvailable() is issued minus held minus pending, so cutting issued
        // below that total drives it negative - and the discovery page, the
        // invest card and the commit guard all read that number.
        $available = CompanyService::sharesAvailable($companyId, (int) $company['shares_issued']);
        $committed = (int) $company['shares_issued'] - $available;

        [$error, $errorStep] = $this->validateEdit($companyData, $asset, $valuation, $assetValue, $committed, $opensAt, $closesAt);

        if ($error !== null) {
            // Merge the submission into the row being edited, so the form comes
            // back with what was typed rather than what is in the database.
            $this->renderEdit(array_merge($company, $companyData), $asset, $activity, $error, $_POST, $errorStep);
            return;
        }

        Company::update($companyId, $companyData);

        if ($asset) {
            Asset::update((int) $asset['id'], [
                'asset_class_id'      => (int) ($_POST['asset_class_id'] ?? 0) ?: null,
                'make'                => trim($_POST['make'] ?? ''),
                'model'               => trim($_POST['model'] ?? ''),
                'year'                => (int) ($_POST['year'] ?? 0) ?: null,
                // Blank stays NULL, never '': the column is UNIQUE and MySQL
                // permits many NULLs but only one empty string.
                'vin'                 => $vin !== '' ? $vin : null,
                'registration_number' => trim($_POST['asset_registration_number'] ?? ''),
                'current_valuation'   => $valuation,
                'valuation_date'      => ($_POST['valuation_date'] ?? '') ?: null,
                'mileage'             => (int) ($_POST['mileage'] ?? 0),
                'insurance_status'    => trim($_POST['insurance_status'] ?? ''),
                'roadworthy_status'   => trim($_POST['roadworthy_status'] ?? ''),
                'asset_status'        => in_array($_POST['asset_status'] ?? '', ['active', 'inactive', 'sold'], true) ? $_POST['asset_status'] : 'active',
            ]);
        }

        $activityChanged = $this->syncCommercialActivity($companyId, $activity);

        $details = "Edited {$companyData['name']} ({$company['reference']})";
        if ($activityChanged) {
            $details .= ' - commercial activity changed, previous period closed';
        }
        if (($company['offer_opens_at'] ?? null) !== $opensAt || ($company['offer_closes_at'] ?? null) !== $closesAt) {
            $details .= ' - offer window changed to ' . ($opensAt ?: 'immediate') . ' until ' . ($closesAt ?: 'fully subscribed');
        }

        // A re-pricing is the change most worth being able to find later, so it
        // is spelled out rather than buried in a generic "edited" entry.
        $navMoved = abs($navPerShare - (float) $company['nav_per_share']) >= 0.0001;
        if ($navMoved) {
            $details .= sprintf(
                ' - NAV re-priced from R%s to R%s a share',
                number_format((float) $company['nav_per_share'], 4),
                number_format($navPerShare, 4)
            );
        }

        $this->audit('edit_company', 'companies', $companyId, $details);

        $this->flash(
            'success',
            $navMoved
                ? 'Saved. NAV is now R' . number_format($navPerShare, 2) . ' a share, applied to every holder.'
                : 'Changes saved to ' . $company['reference'] . '.'
        );

        // Stay in the admin rather than bouncing to the public page. Edits come
        // in batches, and being thrown out after each one meant navigating back
        // in every time.
        $this->redirect('/admin/companies/' . $company['reference'] . '/edit');
    }

    // ------------------------------------------------------------

    /**
     * @return array{0: ?string, 1: int} the message and the step it belongs to,
     *         so the form reopens where the problem is rather than at step one.
     */
    private function validateNewSpv(array $companyData, array $assetData, ?int $assetClassId, ?int $activityTypeId, float $assetValue): array
    {
        if ($companyData['name'] === '') {
            return ['Company name is required.', 1];
        }

        // Classification is required: an SPV with no activity and no asset class
        // is invisible to every filter on the discovery page.
        if (!$assetClassId) {
            return ['Choose an asset class - it drives the discovery filters.', 2];
        }
        if ($assetData['make'] === '' || $assetData['model'] === '') {
            return ['The asset make and model are both required.', 2];
        }
        if ($assetData['valuation_date'] && strtotime($assetData['valuation_date']) > time()) {
            return ['The valuation date cannot be in the future.', 2];
        }
        if ($assetData['purchase_date'] && strtotime($assetData['purchase_date']) > time()) {
            return ['The purchase date cannot be in the future.', 2];
        }
        if (!$activityTypeId) {
            return ['Choose a commercial activity - it decides which industry the SPV appears under.', 3];
        }
        if ($companyData['shares_issued'] < 1) {
            return ['Shares issued must be at least 1 - a company with no shares cannot be invested in.', 4];
        }

        // NAV is derived, so the thing to complain about is the input it comes
        // from. "NAV must be greater than zero" would be describing a field the
        // admin cannot edit.
        if ($assetValue <= 0) {
            return [
                'Add a current valuation or a purchase price for the asset - the share price is calculated from it.',
                2,
            ];
        }
        if ($companyData['nav_per_share'] <= 0) {
            return ['The calculated NAV per share came out at zero. Check the valuation and the share count.', 4];
        }

        return [null, 1];
    }

    private function renderEdit(array $company, $asset = false, $activity = false, ?string $error = null, ?array $old = null, int $errorStep = 1): void
    {
        $companyId = (int) $company['id'];

        if ($asset === false) {
            $assets = Asset::where('company_id', $companyId);
            $asset = $assets[0] ?? null;
        }
        if ($activity === false) {
            $activity = CommercialActivity::latestForCompany($companyId);
        }

        // On a failed submit the asset and activity sections should also show
        // what was typed. Previously only the company block was preserved and
        // asset edits were silently discarded.
        if ($old !== null) {
            if ($asset) {
                $asset = array_merge($asset, array_filter([
                    'asset_class_id'      => $old['asset_class_id'] ?? null,
                    'make'                => $old['make'] ?? null,
                    'model'               => $old['model'] ?? null,
                    'year'                => $old['year'] ?? null,
                    'vin'                 => $old['vin'] ?? null,
                    'registration_number' => $old['asset_registration_number'] ?? null,
                    'current_valuation'   => $old['current_valuation'] ?? null,
                    'valuation_date'      => $old['valuation_date'] ?? null,
                    'mileage'             => $old['mileage'] ?? null,
                    'insurance_status'    => $old['insurance_status'] ?? null,
                    'roadworthy_status'   => $old['roadworthy_status'] ?? null,
                    'asset_status'        => $old['asset_status'] ?? null,
                ], static fn ($v) => $v !== null));
            }
            if ($activity) {
                $activity = array_merge($activity, array_filter([
                    'activity_type_id' => $old['activity_type_id'] ?? null,
                    'operator'         => $old['operator'] ?? null,
                    'location'         => $old['location'] ?? null,
                    'utilisation_rate' => $old['utilisation_rate'] ?? null,
                ], static fn ($v) => $v !== null));
            }
        }

        // The Shares tab needs to state the floor rather than only enforce it.
        $stored = Company::findByReference($company['reference']) ?: $company;
        $available = CompanyService::sharesAvailable($companyId, (int) $stored['shares_issued']);

        $this->render('admin/companies/edit', [
            'company'   => $company,
            'asset'     => $asset,
            'activity'  => $activity,
            'error'     => $error,
            'errorStep' => $errorStep,
            'committed' => max(0, (int) $stored['shares_issued'] - $available),
        ] + $this->taxonomy());
    }

    /**
     * @return array{0: ?string, 1: int} the message and the tab it belongs to.
     */
    private function validateEdit(array $companyData, ?array $asset, float $valuation, float $assetValue, int $committed, ?string $opensAt, ?string $closesAt): array
    {
        if ($companyData['name'] === '') {
            return ['Company name is required.', 1];
        }

        if ($opensAt && $closesAt && strtotime($closesAt) <= strtotime($opensAt)) {
            // An inverted window makes OfferWindow report "scheduled" and
            // "closed" at once, which reads as a broken page rather than a typo.
            return ['The offer must close after it opens.', 2];
        }

        if ($asset !== null) {
            $valuationDate = ($_POST['valuation_date'] ?? '');
            if ($valuationDate && strtotime($valuationDate) > time()) {
                return ['The valuation date cannot be in the future.', 3];
            }
            if ($assetValue <= 0) {
                // NAV is derived, so the complaint belongs on the input it comes
                // from, not on a field the admin cannot edit.
                return ['Add a current valuation for the asset - the share price is calculated from it.', 3];
            }
        }

        if ($companyData['shares_issued'] < 1) {
            return ['Shares issued must be at least 1.', 5];
        }
        if ($companyData['shares_issued'] < $committed) {
            return [
                'This company already has ' . number_format($committed)
                    . ' shares held or committed, so shares issued cannot be set below that.',
                5,
            ];
        }
        if ($companyData['nav_per_share'] <= 0) {
            return ['The calculated NAV per share came out at zero. Check the valuation and the share count.', 5];
        }

        return [null, 1];
    }

    /**
     * `commercial_activities` is a history, not a settings row: each entry is a
     * period the asset spent doing one kind of work. Switching a minibus from a
     * taxi route to staff transport closes the current period and opens a new
     * one; correcting the operator, area or utilisation edits it in place.
     *
     * @return bool whether the activity itself changed
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

    /**
     * A datetime-local field submits `2026-08-14T10:30`. MariaDB tolerates the
     * T, but normalising here keeps the stored value consistent with every
     * other DATETIME in the schema.
     */
    private function dateTimeOrNull(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    // Empty means "not measured", which is different from "measured at zero".
    private function utilisationOrNull(): ?float
    {
        $value = $_POST['utilisation_rate'] ?? '';
        return ($value === '' || !is_numeric($value)) ? null : (float) $value;
    }

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
