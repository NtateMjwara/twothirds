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

        $this->redirect($this->safeReturnPath('/company/' . $company['reference']));
    }

    /**
     * Saving from the discovery grid should land you back where you were, filters
     * and page intact - but the target arrives in a form field, so it can only be
     * a path on this site. Anything with a scheme, a host, or a protocol-relative
     * '//' prefix falls back to the company page.
     */
    private function safeReturnPath(string $fallback): string
    {
        $target = trim((string) ($_POST['return_to'] ?? ''));

        if ($target === '' || $target[0] !== '/' || str_starts_with($target, '//')) {
            return $fallback;
        }
        if (str_contains($target, "\r") || str_contains($target, "\n")) {
            return $fallback;
        }

        return $target;
    }
}
