<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\models\Company;
use app\models\CompanyUpdate;

class CompanyUpdateController extends AdminController
{
    public function index(string $reference): void
    {
        $this->requireAdmin();
        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $this->renderList($company);
    }

    public function store(string $reference): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $happenedOn = trim($_POST['happened_on'] ?? '');

        if ($title === '' || $happenedOn === '') {
            $this->renderList($company, 'A title and a date are both required.');
            return;
        }

        // A timeline entry dated in the future would sit at the top of a public
        // page announcing something that hasn't happened.
        if (strtotime($happenedOn) > strtotime('today 23:59:59')) {
            $this->renderList($company, 'Updates record what has already happened, so the date cannot be in the future.');
            return;
        }

        CompanyUpdate::create([
            'company_id'  => (int) $company['id'],
            'title'       => mb_substr($title, 0, 160),
            'body'        => $body !== '' ? $body : null,
            'happened_on' => $happenedOn,
        ]);

        $this->redirect('/admin/companies/' . $company['reference'] . '/updates');
    }

    public function destroy(string $reference, string $id): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        // Scope the delete to this company so an id from the URL can't reach
        // another company's timeline.
        foreach (CompanyUpdate::forCompany((int) $company['id']) as $update) {
            if ((int) $update['id'] === (int) $id) {
                CompanyUpdate::delete((int) $id);
                break;
            }
        }

        $this->redirect('/admin/companies/' . $company['reference'] . '/updates');
    }

    private function renderList(array $company, ?string $error = null): void
    {
        $this->render('admin/updates/index', [
            'company' => $company,
            'updates' => CompanyUpdate::forCompany((int) $company['id']),
            'error'   => $error,
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
