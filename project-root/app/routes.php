<?php
use app\core\Router;

$router = new Router();

// --- Public ---
$router->get('/', 'HomeController@index');
$router->get('/discover', 'DiscoveryController@index');
$router->get('/company/{reference}', 'CompanyController@show');
$router->get('/documents/{id}', 'DocumentController@download');

// --- Investor auth ---
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');
$router->get('/verify-email/{token}', 'AuthController@verifyEmail');
$router->get('/resend-verification', 'AuthController@showResendVerification');
$router->post('/resend-verification', 'AuthController@resendVerification');
$router->get('/forgot-password', 'AuthController@showForgotPassword');
$router->post('/forgot-password', 'AuthController@sendResetLink');
$router->get('/reset-password/{token}', 'AuthController@showResetPassword');
$router->post('/reset-password/{token}', 'AuthController@resetPassword');

// --- Commit flow (requires investor login) ---
$router->get('/commit/{reference}', 'CommitController@show');
$router->post('/commit/{reference}', 'CommitController@store');

// --- Watchlist ---
$router->post('/watchlist/{reference}/toggle', 'WatchlistController@toggle');

// --- Investor account ---
$router->get('/account/portfolio', 'AccountController@portfolio');
$router->post('/account/commitments/{id}/withdraw', 'AccountController@withdrawCommitment');
$router->get('/account/watchlist', 'AccountController@watchlist');
$router->get('/account/profile', 'AccountController@showProfile');
$router->post('/account/profile', 'AccountController@updateProfile');
$router->get('/account/address', 'AccountController@showAddress');
$router->post('/account/address', 'AccountController@updateAddress');
$router->get('/account/kyc', 'AccountController@showKyc');
$router->post('/account/kyc', 'AccountController@submitKyc');
$router->get('/account/bank', 'AccountController@showBankAccount');
$router->post('/account/bank', 'AccountController@updateBankAccount');
$router->get('/account/notifications', 'AccountController@notifications');
$router->post('/account/notifications/{id}/read', 'AccountController@markNotificationRead');
$router->post('/account/notifications/mark-all-read', 'AccountController@markAllNotificationsRead');

// --- Admin auth ---
$router->get('/admin/login', 'admin\AuthController@showLogin');
$router->post('/admin/login', 'admin\AuthController@login');
$router->get('/admin/logout', 'admin\AuthController@logout');
$router->get('/admin/forgot-password', 'admin\AuthController@showForgotPassword');
$router->post('/admin/forgot-password', 'admin\AuthController@sendResetLink');
$router->get('/admin/reset-password/{token}', 'admin\AuthController@showResetPassword');
$router->post('/admin/reset-password/{token}', 'admin\AuthController@resetPassword');

// --- Admin dashboard, SPV creation/editing, settlement, KYC review ---
$router->get('/admin', 'admin\DashboardController@index');
$router->get('/admin/companies', 'admin\CompanyController@index');
$router->get('/admin/companies/create', 'admin\CompanyController@create');
$router->post('/admin/companies', 'admin\CompanyController@store');
$router->get('/admin/companies/{reference}/edit', 'admin\CompanyController@edit');
$router->post('/admin/companies/{reference}/edit', 'admin\CompanyController@update');
$router->get('/admin/settlement', 'admin\SettlementController@index');
$router->post('/admin/settlement/{id}/confirm', 'admin\SettlementController@confirm');
$router->post('/admin/settlement/{id}/cancel', 'admin\SettlementController@cancel');
$router->get('/admin/kyc', 'admin\KycController@index');
$router->post('/admin/kyc/{id}/approve', 'admin\KycController@approve');
$router->post('/admin/kyc/{id}/reject', 'admin\KycController@reject');
$router->get('/admin/email-queue', 'admin\EmailQueueController@index');

// --- Admin registry and investor management ---
$router->get('/admin/registry', 'admin\RegistryController@index');
$router->get('/admin/investors', 'admin\InvestorController@index');
$router->get('/admin/investors/{id}', 'admin\InvestorController@show');

// --- Admin financial reporting, company documents, directors ---
$router->get('/admin/companies/{reference}/financials', 'admin\FinancialPeriodController@create');
$router->post('/admin/companies/{reference}/financials', 'admin\FinancialPeriodController@store');
$router->get('/admin/companies/{reference}/documents', 'admin\DocumentController@create');
$router->post('/admin/companies/{reference}/documents', 'admin\DocumentController@store');
$router->get('/admin/companies/{reference}/directors', 'admin\DirectorController@index');
$router->post('/admin/companies/{reference}/directors', 'admin\DirectorController@store');
$router->post('/admin/companies/{reference}/directors/{id}/resign', 'admin\DirectorController@resign');
