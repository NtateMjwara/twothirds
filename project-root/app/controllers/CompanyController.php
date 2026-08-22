<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Company;
use app\models\Asset;
use app\models\AssetImage;
use app\models\CommercialActivity;
use app\models\CompanyUpdate;
use app\models\FinancialPeriod;
use app\models\Document;
use app\models\CompanyDirector;
use app\models\Watchlist;
use app\services\CompanyService;
use app\services\CompanySlug;
use app\services\OfferWindow;

class CompanyController extends Controller
{
    /**
     * /discover/invest/{slug}
     *
     * The slug's trailing reference is what resolves; the name in front is
     * decoration. So a link shared before a company was renamed still lands on
     * the right page - and is then redirected to the current canonical slug, so
     * search engines settle on one URL rather than indexing several.
     */
    public function show(string $slug): void
    {
        $reference = CompanySlug::referenceFrom($slug);
        $company = $reference ? Company::findByReference($reference) : null;

        if (!$company) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        if (!CompanySlug::isCanonical($slug, $company['name'], $company['reference'])) {
            // 301, not 302: the canonical form is permanent, and a temporary
            // redirect would leave the old URL in the index indefinitely.
            http_response_code(301);
            header('Location: ' . company_url($company));
            exit;
        }

        $companyId = (int) $company['id'];
        $assets = Asset::where('company_id', $companyId);
        $sharesAvailable = CompanyService::sharesAvailable($companyId, (int) $company['shares_issued']);

        $issued = (int) $company['shares_issued'];
        $taken = max(0, $issued - $sharesAvailable);

        $this->render('company/show', [
            'company'         => $company,
            'asset'           => $assets[0] ?? null,
            'images'          => AssetImage::forCompany($companyId),
            'activities'      => CommercialActivity::where('company_id', $companyId),
            'updates'         => CompanyUpdate::forCompany($companyId),
            'periods'         => FinancialPeriod::forCompany($companyId),
            'latestPeriod'    => FinancialPeriod::latestForCompany($companyId),
            'ttm'             => FinancialPeriod::trailingTwelveMonths($companyId),
            'documents'       => Document::forCompany($companyId),
            'directors'       => CompanyDirector::activeForCompany($companyId),
            'isWatching'      => !empty($_SESSION['user_id'])
                                 && Watchlist::isWatching((int) $_SESSION['user_id'], $companyId),
            'sharesAvailable' => $sharesAvailable,
            'sharesTaken'     => $taken,
            'fundingTarget'   => $issued * (float) $company['nav_per_share'],
            'fundedValue'     => $taken * (float) $company['nav_per_share'],
            'fundedPct'       => $issued > 0 ? min(100, round(($taken / $issued) * 100)) : 0,
            'offer'           => OfferWindow::for($company, $sharesAvailable),
            'crumbs'          => [
                ['label' => 'Discover', 'href' => '/discover'],
                ['label' => 'Invest',   'href' => invest_url()],
                ['label' => $company['name'], 'href' => null],
            ],
        ]);
    }
}
