<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Company;
use app\models\Asset;
use app\models\CommercialActivity;
use app\models\FinancialPeriod;
use app\models\Document;
use app\models\CompanyDirector;
use app\models\Watchlist;
use app\services\CompanyService;

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

        $assets = Asset::where('company_id', $company['id']);
        $isWatching = !empty($_SESSION['user_id'])
            && Watchlist::isWatching((int) $_SESSION['user_id'], (int) $company['id']);

        $this->render('company/show', [
            'company'         => $company,
            'asset'           => $assets[0] ?? null,
            'activities'      => CommercialActivity::where('company_id', $company['id']),
            'latestPeriod'    => FinancialPeriod::latestForCompany($company['id']),
            'ttm'             => FinancialPeriod::trailingTwelveMonths($company['id']),
            'documents'       => Document::forCompany($company['id']),
            'directors'       => CompanyDirector::activeForCompany((int) $company['id']),
            'isWatching'      => $isWatching,
            'sharesAvailable' => CompanyService::sharesAvailable((int) $company['id'], (int) $company['shares_issued']),
        ]);
    }
}
