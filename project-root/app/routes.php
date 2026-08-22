<?php
use app\core\Router;

/*
|--------------------------------------------------------------------------
| URL helpers
|--------------------------------------------------------------------------
|
| company_url() and invest_url() are used by nearly every view. They live
| here, at the top of the routes file, because this is the one file
| guaranteed to be loaded before any controller or view runs - so there is no
| separate wiring step to forget.
|
| That step being forgettable is not hypothetical. When these were shipped as
| a "paste this into helpers.php" snippet, they weren't wired up, and the
| result was a blank white page: PHP fatals on the undefined function while
| the view's output buffer is open, flushes whatever had been rendered so
| far, and never reaches the layout. No header, no stylesheets, no error -
| just the top half of a page in Times New Roman.
|
| require_once, so loading this file twice is harmless.
*/
require_once __DIR__ . '/core/url-helpers.php';

$router = new Router();

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
|
| The offering URLs are nested under /discover so the path describes the
| journey: browse a directory, narrow to a result set, open a company.
|
|   /discover                     the directory
|   /discover/invest              the result set
|   /discover/invest/{slug}       one company
|
| Order matters here. /discover/invest must be declared before the {slug}
| route, or a bare visit to the results page can be captured as a company
| whose slug happens to be empty.
|
| The slug is the company name followed by its reference
| (vukani-mobility-spv-1-spv-00801) and only the reference resolves, so a
| link shared before a rename still works and is redirected to the current
| canonical form.
*/
$router->get('/', 'HomeController@index');
$router->get('/discover', 'DiscoveryController@index');
$router->get('/discover/invest', 'DiscoveryController@browse');
$router->get('/discover/invest/{slug}', 'CompanyController@show');
$router->get('/how-it-works', 'PageController@howItWorks');
$router->get('/fees', 'PageController@fees');
$router->get('/documents/{id}', 'DocumentController@download');

/*
| The legal instruments an investor accepts when committing: the subscription
| agreement, the MOI, the transfer terms, the risk disclosure.
|
| Public and outside the commit flow on purpose. Someone has to be able to read
| the terms before deciding to invest, and a document reachable only from inside
| a checkout is a document nobody reads.
|
| ?company={reference} narrows to that SPV's own version where one exists - an
| individual company's MOI rather than the platform-wide placeholder.
*/
$router->get('/legal/{key}', 'LegalController@show');

/*
|--------------------------------------------------------------------------
| Permanent redirects from the retired paths
|--------------------------------------------------------------------------
|
| /browse and /company/{reference} were live, linked from the homepage and
| the fees page, and quite possibly bookmarked or indexed. Retiring them
| without redirects turns every one of those into a 404 - and unlike an
| internal link, someone else's bookmark can't be gone back and fixed.
|
| The sector moved from a path segment to a query parameter, so
| /browse/{sector} carries it across rather than dropping it.
|
| Cheap to keep. Delete them when the access logs stop showing traffic.
*/
$router->get('/browse', 'LegacyRedirectController@browse');
$router->get('/browse/{sector}', 'LegacyRedirectController@browse');
$router->get('/company/{reference}', 'LegacyRedirectController@company');

/*
|--------------------------------------------------------------------------
| Investor auth
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| Commit flow (requires investor login)
|--------------------------------------------------------------------------
|
| Still keyed by reference rather than slug. These aren't pages anyone links
| to or indexes - they're steps in a transaction reached from a button - so
| there is nothing to gain from a readable URL, and the reference is the
| stable identifier the commitment record is written against.
|
| CommitController re-checks everything on the POST, not only when the form
| renders: the offer window can close, KYC can be revoked, and the last shares
| can be taken between loading a page and submitting it. A hidden button is not
| a control - this URL can be typed.
|
| The POST also refuses unless every required legal document has been accepted,
| checked against the documents the server chose to show rather than against
| what the form posted back. On success it prices the commitment, records the
| consent, and emails an invoice.
*/
$router->get('/commit/{reference}', 'CommitController@show');
$router->post('/commit/{reference}', 'CommitController@store');

/*
|--------------------------------------------------------------------------
| Watchlist
|--------------------------------------------------------------------------
*/
$router->post('/watchlist/{reference}/toggle', 'WatchlistController@toggle');

/*
|--------------------------------------------------------------------------
| Investor account
|--------------------------------------------------------------------------
|
| Five profile sections, each its own page behind the shared account header.
|
| Note the two renamed bank methods: an investor can now hold more than one
| account, so showBankAccount/updateBankAccount became
| showBankAccounts/storeBankAccount. The old names will 500.
*/
$router->get('/account/portfolio', 'AccountController@portfolio');
$router->post('/account/commitments/{id}/withdraw', 'AccountController@withdrawCommitment');
$router->get('/account/watchlist', 'AccountController@watchlist');

$router->get('/account/profile', 'AccountController@showProfile');
$router->post('/account/profile', 'AccountController@updateProfile');
$router->get('/account/address', 'AccountController@showAddress');
$router->post('/account/address', 'AccountController@updateAddress');
$router->get('/account/kyc', 'AccountController@showKyc');
$router->post('/account/kyc', 'AccountController@submitKyc');
$router->get('/account/tax', 'AccountController@showTax');
$router->post('/account/tax', 'AccountController@updateTax');

$router->get('/account/bank', 'AccountController@showBankAccounts');
$router->post('/account/bank', 'AccountController@storeBankAccount');
$router->post('/account/bank/{id}/primary', 'AccountController@makeBankAccountPrimary');
$router->post('/account/bank/{id}/delete', 'AccountController@deleteBankAccount');

$router->get('/account/notifications', 'AccountController@notifications');
$router->post('/account/notifications/{id}/read', 'AccountController@markNotificationRead');
$router->post('/account/notifications/mark-all-read', 'AccountController@markAllNotificationsRead');

/*
|--------------------------------------------------------------------------
| Admin auth
|--------------------------------------------------------------------------
*/
$router->get('/admin/login', 'admin\AuthController@showLogin');
$router->post('/admin/login', 'admin\AuthController@login');
$router->get('/admin/logout', 'admin\AuthController@logout');
$router->get('/admin/forgot-password', 'admin\AuthController@showForgotPassword');
$router->post('/admin/forgot-password', 'admin\AuthController@sendResetLink');
$router->get('/admin/reset-password/{token}', 'admin\AuthController@showResetPassword');
$router->post('/admin/reset-password/{token}', 'admin\AuthController@resetPassword');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
|
| Every action behind these is gated by a permission, not just by being
| logged in - see AdminPolicy. An ops account can't settle commitments and a
| finance account can't approve identity documents.
|
| /admin/companies/create is declared before the {reference} routes. It has
| a different segment count so the router wouldn't confuse them either way,
| but the ordering makes the intent obvious to the next reader.
*/
$router->get('/admin', 'admin\DashboardController@index');

$router->get('/admin/companies', 'admin\CompanyController@index');
$router->get('/admin/companies/create', 'admin\CompanyController@create');
$router->post('/admin/companies', 'admin\CompanyController@store');
$router->get('/admin/companies/{reference}/edit', 'admin\CompanyController@edit');
$router->post('/admin/companies/{reference}/edit', 'admin\CompanyController@update');

// --- Settlement (finance, super_admin) ---
$router->get('/admin/settlement', 'admin\SettlementController@index');
$router->post('/admin/settlement/{id}/confirm', 'admin\SettlementController@confirm');
$router->post('/admin/settlement/{id}/cancel', 'admin\SettlementController@cancel');

// --- KYC review (ops, super_admin) ---
//
// The reveal route exists because ID numbers are masked in the queue. A full
// number is shown one row at a time, and every reveal is written to the audit
// log - so checking one document leaves a different trace from paging through
// copying numbers.
$router->get('/admin/kyc', 'admin\KycController@index');
$router->post('/admin/kyc/{id}/approve', 'admin\KycController@approve');
$router->post('/admin/kyc/{id}/reject', 'admin\KycController@reject');
$router->post('/admin/kyc/{id}/reveal', 'admin\KycController@reveal');

$router->get('/admin/email-queue', 'admin\EmailQueueController@index');

// --- Registry and investor management ---
$router->get('/admin/registry', 'admin\RegistryController@index');
$router->get('/admin/investors', 'admin\InvestorController@index');
$router->get('/admin/investors/{id}', 'admin\InvestorController@show');

// --- Per-company admin screens ---
//
// These seven share the sub-navigation in _company-tabs.php, so the paths have
// to match the tab keys there: edit, images, updates, financials, documents,
// directors, banking.
$router->get('/admin/companies/{reference}/financials', 'admin\FinancialPeriodController@create');
$router->post('/admin/companies/{reference}/financials', 'admin\FinancialPeriodController@store');

$router->get('/admin/companies/{reference}/documents', 'admin\DocumentController@create');
$router->post('/admin/companies/{reference}/documents', 'admin\DocumentController@store');

$router->get('/admin/companies/{reference}/directors', 'admin\DirectorController@index');
$router->post('/admin/companies/{reference}/directors', 'admin\DirectorController@store');
$router->post('/admin/companies/{reference}/directors/{id}/resign', 'admin\DirectorController@resign');

// --- Where investors pay ---
//
// The SPV's own bank account, shown on invoices and nowhere else. Kept on its
// own screen rather than folded into the edit form: it is the only place on the
// platform where an account number is entered by someone other than its owner,
// and every change to it earns its own audit entry.
//
// Until this is filled in, the commit flow blocks the company - an invoice
// telling an investor to pay with nowhere to pay it is worse than a disabled
// button.
$router->get('/admin/companies/{reference}/banking', 'admin\CompanyBankController@edit');
$router->post('/admin/companies/{reference}/banking', 'admin\CompanyBankController@update');

// --- Asset photographs ---
$router->get('/admin/companies/{reference}/images', 'admin\AssetImageController@index');
$router->post('/admin/companies/{reference}/images', 'admin\AssetImageController@store');
$router->post('/admin/companies/{reference}/images/{id}/primary', 'admin\AssetImageController@makePrimary');
$router->post('/admin/companies/{reference}/images/{id}/delete', 'admin\AssetImageController@destroy');

// --- Company timeline ---
$router->get('/admin/companies/{reference}/updates', 'admin\CompanyUpdateController@index');
$router->post('/admin/companies/{reference}/updates', 'admin\CompanyUpdateController@store');
$router->post('/admin/companies/{reference}/updates/{id}/delete', 'admin\CompanyUpdateController@destroy');

return $router;
