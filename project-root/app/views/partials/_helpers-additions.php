<?php
/**
 * Add these to app/core/helpers.php.
 *
 * Every link to a company or to the results page now goes through one function.
 * That is the point: the path has changed twice already, and the next change
 * should be one edit rather than a search across a dozen views.
 */

use app\services\CompanySlug;

/**
 * The public URL for a company.
 *
 * Takes a row with `name` and `reference` - which every listing query already
 * returns - so call sites don't have to build the slug themselves.
 */
function company_url(array $company): string
{
    return '/discover/invest/' . CompanySlug::make(
        (string) ($company['name'] ?? ''),
        (string) ($company['reference'] ?? '')
    );
}

/** The results page, with an optional filter set. */
function invest_url(array $filters = []): string
{
    $filters = array_filter($filters, static fn ($v) => $v !== null && $v !== '');

    return '/discover/invest' . ($filters ? '?' . http_build_query($filters) : '');
}
