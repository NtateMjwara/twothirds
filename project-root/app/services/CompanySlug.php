<?php
namespace app\services;

/**
 * Company slugs for /discover/invest/{slug}.
 *
 * The slug is the company name followed by its reference:
 *
 *     Vukani Mobility SPV 1  ->  vukani-mobility-spv-1-spv-00801
 *
 * The reference on the end is what actually resolves. The name in front is for
 * the reader and for search engines, and is ignored on lookup.
 *
 * That decision is worth spelling out, because the obvious alternative is a
 * `slug` column on `companies`:
 *
 *   - No migration, no backfill, no uniqueness constraint to police, and no
 *     collision logic for two companies with the same name.
 *   - Renaming a company can't break an existing link. The old slug still
 *     resolves, and the controller redirects it to the current canonical form,
 *     so a shared or indexed URL never 404s.
 *   - A stored slug would have to be regenerated on every rename, and any link
 *     issued before the rename would die unless a redirect table were kept too.
 *
 * It's the same pattern Stack Overflow uses on question URLs, for the same
 * reason.
 */
class CompanySlug
{
    /** Matches the reference at the end of a slug: spv-00801, SPV-123456. */
    private const REFERENCE_PATTERN = '/(spv-\d{3,})$/i';

    public static function make(string $name, string $reference): string
    {
        $namePart = self::slugify($name);
        $referencePart = strtolower($reference);

        return $namePart !== '' ? "{$namePart}-{$referencePart}" : $referencePart;
    }

    /**
     * The reference a slug points at, or null when there isn't one.
     *
     * Accepts a bare reference too ("SPV-00801"), so old links and anything
     * hand-typed still work.
     */
    public static function referenceFrom(string $slug): ?string
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        return preg_match(self::REFERENCE_PATTERN, $slug, $m)
            ? strtoupper($m[1])
            : null;
    }

    /** True when the slug is already the canonical form for this company. */
    public static function isCanonical(string $slug, string $name, string $reference): bool
    {
        return $slug === self::make($name, $reference);
    }

    /**
     * Name to URL segment.
     *
     * Accents are folded where iconv can manage it, so "Kruger Trail Véhicules"
     * becomes "kruger-trail-vehicules" rather than losing the word. iconv is
     * guarded because it can fail on some locales, and a slug is not worth a
     * fatal error.
     */
    private static function slugify(string $value): string
    {
        $value = trim($value);

        if (function_exists('iconv')) {
            $folded = @iconv('UTF-8', 'ASCII//TRANSLIT', $value);
            if ($folded !== false) {
                $value = $folded;
            }
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
