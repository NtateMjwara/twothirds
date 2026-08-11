<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Company;
use app\models\Watchlist;

class WatchlistController extends Controller
{
    public function toggle(string $reference): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $company = Company::findByReference($reference);
        if (!$company) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $companyId = (int) $company['id'];

        if (Watchlist::isWatching($userId, $companyId)) {
            Watchlist::remove($userId, $companyId);
        } else {
            Watchlist::create(['user_id' => $userId, 'company_id' => $companyId]);
        }

        $this->redirect('/company/' . $company['reference']);
    }
}
