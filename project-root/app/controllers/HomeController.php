<?php
namespace app\controllers;

use app\core\Controller;
use app\services\CompanyService;

class HomeController extends Controller
{
    public function index(): void
    {
        $listings = CompanyService::activeListings();
        $this->render('home', [
            'featured' => array_slice($listings, 0, 3),
        ]);
    }
}
