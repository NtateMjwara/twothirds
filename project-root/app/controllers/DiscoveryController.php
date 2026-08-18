<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Sector;
use app\models\Watchlist;
use app\services\DiscoveryService;

class DiscoveryController extends Controller
{
    /**
     * /discover — the landing page.
     *
     * Browse only: search, industries, commercial activities, saved listings and
     * themes. It runs no listing query at all. Every route out of here lands on
     * /browse with the relevant filter already applied, which is what lets the
     * two pages have genuinely different jobs instead of one page trying to be
     * both a directory and a result set.
     */
    public function index(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $watchlist = $userId ? DiscoveryService::watchlistSnapshot($userId) : [];
        $watchlistIsOwn = $watchlist !== [];
        if (!$watchlistIsOwn) {
            $watchlist = DiscoveryService::mostWatched(6);
        }

        $this->render('discovery/index', [
            'sectors'        => DiscoveryService::sectorFacets(),
            // The whole controlled list, not the busiest handful: this is the
            // directory of what the platform covers, so an activity with no live
            // listing still belongs on it.
            'activities'     => DiscoveryService::activityFacets('', 200),
            'themes'         => DiscoveryService::themes(),
            'totalActive'    => DiscoveryService::activeListingCount(),
            'locations'      => DiscoveryService::locationFacets(),
            'watchlist'      => $watchlist,
            'watchlistIsOwn' => $watchlistIsOwn,
        ]);
    }

    /**
     * /browse and /browse/{sector} — the result set.
     *
     * The sector may arrive in the path or the query string. The path form
     * exists so an industry page is a shareable, indexable URL rather than a
     * query string someone has to construct.
     */
    public function browse(?string $sector = null): void
    {
        $filters = [];
        foreach (DiscoveryService::emptyFilters() as $key => $default) {
            $filters[$key] = trim((string) ($_GET[$key] ?? $default));
        }

        if ($sector !== null && $sector !== '') {
            $filters['sector'] = $sector;
        }

        // An unknown theme or sort should show everything rather than nothing.
        if ($filters['theme'] !== '' && !isset(DiscoveryService::themes()[$filters['theme']])) {
            $filters['theme'] = '';
        }
        if ($filters['sort'] !== '' && !isset(DiscoveryService::sortOptions()[$filters['sort']])) {
            $filters['sort'] = '';
        }

        $activeSector = $filters['sector'] !== '' ? Sector::findBySlug($filters['sector']) : null;

        // A slug that matches no sector would silently return zero results and
        // look like an empty catalogue rather than a bad link.
        if ($filters['sector'] !== '' && !$activeSector) {
            $filters['sector'] = '';
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = DiscoveryService::listings($filters, $page);

        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $this->render('discovery/browse', [
            'result'       => $result,
            'companies'    => $result['rows'],
            'filters'      => $filters,
            'sectors'      => DiscoveryService::sectorFacets(),
            'activeSector' => $activeSector,
            'activities'   => DiscoveryService::activityFacets($filters['sector'], 40),
            'assetClasses' => DiscoveryService::assetClassFacets(),
            'locations'    => DiscoveryService::locationFacets(),
            'themes'       => DiscoveryService::themes(),
            'sortOptions'  => DiscoveryService::sortOptions(),
            'totalActive'  => DiscoveryService::activeListingCount(),
            'watchedIds'   => $userId ? Watchlist::companyIdsForUser($userId) : [],
        ]);
    }
}
