<?php
namespace app\controllers;

use app\core\Controller;
use app\services\CompanyService;

class DiscoveryController extends Controller
{
    public function index(): void
    {
        $companies = CompanyService::activeListings();
        $this->render('discovery/index', ['companies' => $companies]);
    }
}
