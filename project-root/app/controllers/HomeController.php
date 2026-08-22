<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Watchlist;
use app\services\DiscoveryService;

class HomeController extends Controller
{
    public function index(): void
    {
        // Four newest, straight from the discovery read model.
        //
        // The homepage used to fetch its own thinner set of columns, which is
        // why its cards were a bespoke design: they simply didn't have the
        // fields the real card needs - cover photograph, funding percentage,
        // shares available, sector. Using the same query means the same card,
        // and the homepage can't drift away from the rest of the site again.
        //
        // Asking for one page of four does the LIMIT in SQL rather than
        // fetching every active listing and throwing most of it away.
        $featured = DiscoveryService::listings(
            ['sort' => 'newest'] + DiscoveryService::emptyFilters(),
            1,
            4
        )['rows'];

        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $this->render('home', [
            'featured' => $featured,
            // So the save control on each card shows the right state for
            // someone already signed in.
            'watchedIds' => $userId ? Watchlist::companyIdsForUser($userId) : [],
        ]);
    }
}
