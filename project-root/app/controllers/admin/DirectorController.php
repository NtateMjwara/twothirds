<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\models\Company;
use app\models\CompanyDirector;

class DirectorController extends AdminController
{
    public function index(string $reference): void
    {
        $this->requireAdmin();
        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $this->render('admin/directors/index', [
            'company'   => $company,
            'directors' => CompanyDirector::forCompany((int) $company['id']),
        ]);
    }

    public function store(string $reference): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $fullName = trim($_POST['full_name'] ?? '');
        $role = trim($_POST['role'] ?? '') ?: 'Director';
        $appointedDate = $_POST['appointed_date'] ?: date('Y-m-d');

        if ($fullName === '') {
            $this->render('admin/directors/index', [
                'company'   => $company,
                'directors' => CompanyDirector::forCompany((int) $company['id']),
                'error'     => 'Director name is required.',
            ]);
            return;
        }

        CompanyDirector::create([
            'company_id'     => $company['id'],
            'full_name'      => $fullName,
            'role'           => $role,
            'appointed_date' => $appointedDate,
            'status'         => 'active',
        ]);

        $this->redirect('/admin/companies/' . $company['reference'] . '/directors');
    }

    public function resign(string $reference, string $id): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        CompanyDirector::update((int) $id, ['status' => 'resigned']);

        $this->redirect('/admin/companies/' . $company['reference'] . '/directors');
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
