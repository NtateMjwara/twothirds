<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Sector;
use app\models\Watchlist;
use app\services\DiscoveryService;

class DiscoveryController extends Controller
{
    public function index(): void
    {
        $filters = [];
        foreach (DiscoveryService::emptyFilters() as $key => $default) {
            $filters[$key] = trim((string) ($_GET[$key] ?? $default));
        }

        // An unknown theme slug should show everything rather than nothing.
        if ($filters['theme'] !== '' && !isset(DiscoveryService::themes()[$filters['theme']])) {
            $filters['theme'] = '';
        }
        if ($filters['sort'] !== '' && !isset(DiscoveryService::sortOptions()[$filters['sort']])) {
            $filters['sort'] = '';
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = DiscoveryService::listings($filters, $page);

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $watchlist = $userId ? DiscoveryService::watchlistSnapshot($userId) : [];
        // Signed out, or signed in with nothing saved yet: show what other
        // investors are following instead of an empty shelf.
        $watchlistIsOwn = $watchlist !== [];
        if (!$watchlistIsOwn) {
            $watchlist = DiscoveryService::mostWatched(6);
        }

        $this->render('discovery/index', [
            'result'          => $result,
            'companies'       => $result['rows'],
            'filters'         => $filters,
            'sectors'         => DiscoveryService::sectorFacets(),
            'activeSector'    => $filters['sector'] !== '' ? Sector::findBySlug($filters['sector']) : null,
            'activities'      => DiscoveryService::activityFacets($filters['sector']),
            'assetClasses'    => DiscoveryService::assetClassFacets(),
            'locations'       => DiscoveryService::locationFacets(),
            'themes'          => DiscoveryService::themes(),
            'sortOptions'     => DiscoveryService::sortOptions(),
            'totalActive'     => DiscoveryService::activeListingCount(),
            'watchlist'       => $watchlist,
            'watchlistIsOwn'  => $watchlistIsOwn,
            'watchedIds'      => $userId ? Watchlist::companyIdsForUser($userId) : [],
        ]);
    }
}
