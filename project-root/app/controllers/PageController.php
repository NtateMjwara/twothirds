<?php
namespace app\controllers;

use app\core\Controller;
use app\services\DiscoveryService;

class PageController extends Controller
{
    public function howItWorks(): void
    {
        $this->render('pages/how-it-works', [
            'sectors'     => DiscoveryService::sectorFacets(),
            'totalActive' => DiscoveryService::activeListingCount(),
        ]);
    }
}
