<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\models\Company;
use app\models\CompanyDirector;

class DirectorController extends AdminController
{
    public function index(string $reference): void
    {
        $this->requirePermission('director.manage');
        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $this->renderList($company);
    }

    public function store(string $reference): void
    {
        $this->requirePermission('director.manage');
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $fullName = trim($_POST['full_name'] ?? '');
        $role = trim($_POST['role'] ?? '') ?: 'Director';
        // Was $_POST['appointed_date'] ?: ... which warns on PHP 8 when the
        // field isn't submitted at all.
        $appointedDate = ($_POST['appointed_date'] ?? '') ?: date('Y-m-d');

        if ($fullName === '') {
            $this->renderList($company, 'Director name is required.');
            return;
        }

        // A director appointed in the future hasn't been appointed.
        if (strtotime($appointedDate) > strtotime('today 23:59:59')) {
            $this->renderList($company, 'The appointment date cannot be in the future.');
            return;
        }

        CompanyDirector::create([
            'company_id'     => (int) $company['id'],
            'full_name'      => mb_substr($fullName, 0, 150),
            'role'           => mb_substr($role, 0, 100),
            'appointed_date' => $appointedDate,
            'status'         => 'active',
        ]);

        $this->audit('add_director', 'companies', (int) $company['id'], "Appointed {$fullName} as {$role}");
        // Flash text is escaped by the layout, so it is stored raw here.
        $this->flash('success', $fullName . ' added as ' . $role . '.');

        $this->redirect('/admin/companies/' . $company['reference'] . '/directors');
    }

    public function resign(string $reference, string $id): void
    {
        $this->requirePermission('director.manage');
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        // The director id arrives in the URL and was previously used directly.
        // /admin/companies/ANY-REFERENCE/directors/{id}/resign would resign any
        // director on the platform, because nothing checked the two matched.
        $director = null;
        foreach (CompanyDirector::forCompany((int) $company['id']) as $row) {
            if ((int) $row['id'] === (int) $id) {
                $director = $row;
                break;
            }
        }

        if (!$director) {
            $this->flash('error', 'That director is not on this company.');
            $this->redirect('/admin/companies/' . $company['reference'] . '/directors');
            return;
        }

        if ($director['status'] === 'resigned') {
            $this->flash('warning', $director['full_name'] . ' is already marked resigned.');
            $this->redirect('/admin/companies/' . $company['reference'] . '/directors');
            return;
        }

        CompanyDirector::update((int) $director['id'], ['status' => 'resigned']);
        $this->audit('resign_director', 'companies', (int) $company['id'], "Marked {$director['full_name']} resigned");
        $this->flash('success', $director['full_name'] . ' marked resigned.');

        $this->redirect('/admin/companies/' . $company['reference'] . '/directors');
    }

    private function renderList(array $company, ?string $error = null): void
    {
        $this->render('admin/directors/index', [
            'company'   => $company,
            'directors' => CompanyDirector::forCompany((int) $company['id']),
            'error'     => $error,
        ]);
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
