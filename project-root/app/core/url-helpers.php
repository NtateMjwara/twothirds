<?php
/**
 * URL helpers.
 *
 * Loaded once from the front controller, next to helpers.php:
 *
 *     require __DIR__ . '/../app/core/url-helpers.php';
 *
 * Kept in its own file rather than pasted into helpers.php so that applying an
 * update is a file copy rather than a merge into something you have edited.
 *
 * Every link to a company or to the results page goes through these two
 * functions. That is the point: the path has changed twice, and each time the
 * cost was hunting links across a dozen views. The next change should be one
 * edit here.
 */

use app\services\CompanySlug;

/*
 * Note on load order: this file is required from routes.php, which runs after
 * the autoloader is registered but before any controller. CompanySlug is
 * therefore resolved lazily, at call time, not when this file is parsed - so
 * the require here is safe wherever it sits in the boot sequence.
 */

if (!function_exists('company_url')) {
    /**
     * The public URL for a company: /discover/invest/{name}-{reference}
     *
     * Takes a row with `name` and `reference`, which every listing query
     * already returns, so call sites never build the slug themselves.
     */
    function company_url(array $company): string
    {
        return '/discover/invest/' . CompanySlug::make(
            (string) ($company['name'] ?? ''),
            (string) ($company['reference'] ?? '')
        );
    }
}

if (!function_exists('invest_url')) {
    /** The results page, with an optional filter set. */
    function invest_url(array $filters = []): string
    {
        $filters = array_filter($filters, static fn ($v) => $v !== null && $v !== '');

        return '/discover/invest' . ($filters ? '?' . http_build_query($filters) : '');
    }
}
