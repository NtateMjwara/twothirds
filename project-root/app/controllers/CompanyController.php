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
use app\services\OfferWindow;

class CompanyController extends Controller
{
    public function show(string $reference): void
    {
        $company = Company::findByReference($reference);

        if (!$company) {
            http_response_code(404);
            $this->render('errors/404');
            return;
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
            // Expressed in rands as well as shares: "R2.1m of R5.4m" is easier to
            // hold in your head than "4,100 of 10,000 shares".
            'fundingTarget'   => $issued * (float) $company['nav_per_share'],
            'fundedValue'     => $taken * (float) $company['nav_per_share'],
            'fundedPct'       => $issued > 0 ? min(100, round(($taken / $issued) * 100)) : 0,
            'offer'           => OfferWindow::for($company, $sharesAvailable),
        ]);
    }
}
