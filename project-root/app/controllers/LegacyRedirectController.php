<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Company;

/**
 * Permanent redirects from the old paths.
 *
 * /browse and /company/{reference} have been live, linked from the homepage and
 * the fees page, and quite possibly bookmarked or indexed. Retiring them without
 * redirects would turn every one of those into a 404 - and unlike an internal
 * link, there is no way to go back and fix someone else's bookmark.
 *
 * These are cheap to keep. Delete them when the logs stop showing traffic.
 */
class LegacyRedirectController extends Controller
{
    /** /browse and /browse/{sector} -> /discover/invest */
    public function browse(?string $sector = null): void
    {
        $filters = $_GET;

        if ($sector !== null && $sector !== '') {
            // The sector used to be a path segment and is now a query
            // parameter, so it has to move rather than simply be dropped.
            $filters['sector'] = $sector;
        }

        $this->permanent(invest_url($filters));
    }

    /** /company/{reference} -> /discover/invest/{slug} */
    public function company(string $reference): void
    {
        $company = Company::findByReference($reference);

        if (!$company) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $this->permanent(company_url($company));
    }

    private function permanent(string $target): void
    {
        http_response_code(301);
        header('Location: ' . $target);
        exit;
    }
}
