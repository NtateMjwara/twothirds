<?php
namespace app\controllers;

use app\core\Controller;
use app\services\CompanyService;

class DiscoveryController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'category' => trim((string) ($_GET['category'] ?? '')),
            'location' => trim((string) ($_GET['location'] ?? '')),
            'asset_type' => trim((string) ($_GET['asset_type'] ?? '')),
            'min_price' => trim((string) ($_GET['min_price'] ?? '')),
            'max_price' => trim((string) ($_GET['max_price'] ?? '')),
            'sort' => trim((string) ($_GET['sort'] ?? '')),
        ];

        $this->render('discovery/index', [
            'companies' => CompanyService::filteredListings($filters),
            'facets' => CompanyService::filterFacets(),
            'filters' => $filters,
            'totalActive' => CompanyService::activeListingCount(),
        ]);
    }
}
