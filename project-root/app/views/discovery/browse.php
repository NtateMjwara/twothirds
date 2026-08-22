<?php
/**
 * /discover/invest — the result set.
 *
 * Two chip rows narrow the set (industry, then activity within it), a filter
 * panel handles the rest, and the grid below is paginated. Every control writes
 * to the query string, so any view of this page is a URL that can be sent to
 * someone.
 */
$activeSort = $filters['sort'] !== '' ? $filters['sort'] : 'newest';

// Sort rearranges results, it doesn't narrow them, so it isn't counted as an
// active filter for the badge or the clear-all prompt.
$activeFilterCount = count(array_filter(
    array_diff_key($filters, ['sort' => true]),
    static fn ($v) => $v !== ''
));

$paginationBase = '/discover/invest';
$returnTo = $paginationBase . query_with($filters, ['page' => $result['page']]);

// Reprints filter state that a link-driven control owns, so submitting a form
// elsewhere on the page doesn't silently drop it.
$carry = static function (array $keys) use ($filters): void {
    foreach ($keys as $key) {
        if (($filters[$key] ?? '') !== '') {
            echo '<input type="hidden" name="' . e($key) . '" value="' . e($filters[$key]) . '">' . "\n";
        }
    }
};

$heading = $activeSector['name'] ?? 'All offerings';
?>
<?php require __DIR__ . '/../partials/_breadcrumbs.php'; ?>

<section class="browse-masthead">
    <h1><?= e($heading) ?></h1>
    <?php if ($activeSector && !empty($activeSector['tagline'])): ?>
        <p class="muted browse-tagline"><?= e($activeSector['tagline']) ?></p>
    <?php endif; ?>

    <form method="get" action="/discover/invest" class="discovery-search" role="search">
        <?php $carry(['sector', 'activity', 'asset_class', 'location', 'min_price', 'max_price', 'availability', 'theme', 'sort']); ?>
        <label for="browse-q" class="sr-only">Search offerings</label>
        <i class="ti ti-search discovery-search-icon" aria-hidden="true"></i>
        <input type="search" id="browse-q" name="q" value="<?= e($filters['q']) ?>"
               placeholder="Search all available offerings&hellip;" autocomplete="off">
        <button type="submit" class="btn discovery-search-btn" aria-label="Search">
            <i class="ti ti-search" aria-hidden="true"></i>
        </button>
    </form>
</section>

<!-- Commercial activity, scoped to the chosen industry.
     
     There is no industry rail here any more. The industry is chosen on
     Discover, and repeating twelve chips at the top of every result set spent a
     screenful re-asking a question that had already been answered. It lives in
     the Add filter panel instead, so it can still be changed without going
     back, and the active-filter chip below still removes it in one click. -->
<?php if (!empty($activities)): ?>
<div class="filter-rail-group">
    <p class="filter-rail-label">
        Filter by commercial activity
        <?php if ($filters['sector'] === ''): ?>
            <span class="muted">&mdash; busiest across all industries</span>
        <?php endif; ?>
    </p>
    <div class="pill-cloud pill-cloud-inline" role="group" aria-label="Commercial activities">
        <a class="activity-pill<?= $filters['activity'] === '' ? ' is-active' : '' ?>"
           href="/discover/invest<?= e(query_with($filters, ['activity' => null, 'page' => null])) ?>">
            <span class="pill-label">All activities</span>
        </a>
        <?php foreach ($activities as $a): ?>
            <a class="activity-pill<?= $filters['activity'] === $a['slug'] ? ' is-active' : '' ?><?= (int) $a['listing_count'] === 0 ? ' is-empty' : '' ?>"
               href="/discover/invest<?= e(query_with($filters, ['activity' => $a['slug'], 'page' => null])) ?>"
               title="<?= e($a['description'] ?? '') ?>">
                <span class="pill-icon"><i class="ti <?= e($a['icon'] ?: 'ti-briefcase') ?>" aria-hidden="true"></i></span>
                <span class="pill-label"><?= e($a['name']) ?></span>
                <?php if ((int) $a['listing_count'] > 0): ?>
                    <span class="pill-count"><?= number_format((int) $a['listing_count']) ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     Filter bar
     ============================================================ -->
<div class="filter-bar">
    <div class="filter-bar-head">
        <span class="filter-bar-title"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i> Filters</span>

        <?php if ($activeFilterCount): ?>
            <div class="active-filter-chips">
                <?php if ($filters['q'] !== ''): ?>
                    <a class="chip" href="/discover/invest<?= e(query_with($filters, ['q' => null, 'page' => null])) ?>">
                        &ldquo;<?= e($filters['q']) ?>&rdquo; <i class="ti ti-x" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <?php if ($activeSector): ?>
                    <a class="chip" href="/discover/invest<?= e(query_with($filters, ['sector' => null, 'activity' => null, 'page' => null])) ?>">
                        <i class="ti <?= e($activeSector['icon']) ?>" aria-hidden="true"></i> <?= e($activeSector['name']) ?> <i class="ti ti-x" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <?php if ($filters['activity'] !== ''): ?>
                    <a class="chip" href="/discover/invest<?= e(query_with($filters, ['activity' => null, 'page' => null])) ?>">
                        <?= e(str_replace('-', ' ', $filters['activity'])) ?> <i class="ti ti-x" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <?php if ($filters['asset_class'] !== ''): ?>
                    <a class="chip" href="/discover/invest<?= e(query_with($filters, ['asset_class' => null, 'page' => null])) ?>">
                        <?= e(str_replace('-', ' ', $filters['asset_class'])) ?> <i class="ti ti-x" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <?php if ($filters['location'] !== ''): ?>
                    <a class="chip" href="/discover/invest<?= e(query_with($filters, ['location' => null, 'page' => null])) ?>">
                        <i class="ti ti-map-pin" aria-hidden="true"></i> <?= e($filters['location']) ?> <i class="ti ti-x" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <?php if ($filters['theme'] !== '' && isset($themes[$filters['theme']])): ?>
                    <a class="chip" href="/discover/invest<?= e(query_with($filters, ['theme' => null, 'page' => null])) ?>">
                        <?= e($themes[$filters['theme']]['label']) ?> <i class="ti ti-x" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <?php if ($filters['availability'] !== ''): ?>
                    <a class="chip" href="/discover/invest<?= e(query_with($filters, ['availability' => null, 'page' => null])) ?>">
                        <?= $filters['availability'] === 'open' ? 'Open to invest' : 'Fully subscribed' ?> <i class="ti ti-x" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <?php if ($filters['min_price'] !== '' || $filters['max_price'] !== ''): ?>
                    <a class="chip" href="/discover/invest<?= e(query_with($filters, ['min_price' => null, 'max_price' => null, 'page' => null])) ?>">
                        R<?= e($filters['min_price'] !== '' ? $filters['min_price'] : '0') ?>&ndash;<?= $filters['max_price'] !== '' ? 'R' . e($filters['max_price']) : '&infin;' ?>
                        <i class="ti ti-x" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <a class="chip chip-clear" href="/discover/invest">Clear all</a>
            </div>
        <?php else: ?>
            <span class="muted filter-bar-empty">Nothing applied &mdash; showing every live offering.</span>
        <?php endif; ?>

        <details class="add-filter">
            <summary class="btn-outline add-filter-btn">
                Add filter<?= $activeFilterCount ? " ({$activeFilterCount})" : '' ?>
                <i class="ti ti-chevron-down" aria-hidden="true"></i>
            </summary>

            <form method="get" action="/discover/invest" class="add-filter-panel">
                <?php $carry(['q', 'activity', 'theme', 'sort']); ?>

                <div class="filter-group">
                    <label for="f-sector">Industry</label>
                    <select id="f-sector" name="sector">
                        <option value="">All industries</option>
                        <?php foreach ($sectors as $sector): ?>
                            <option value="<?= e($sector['slug']) ?>"<?= $filters['sector'] === $sector['slug'] ? ' selected' : '' ?>>
                                <?= e($sector['name']) ?> (<?= (int) $sector['listing_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="muted small-note">
                        Usually chosen on Discover. Change it here to look somewhere else
                        without going back.
                    </p>
                </div>

                <div class="filter-group">
                    <span class="filter-group-label">Availability</span>
                    <select name="availability">
                        <option value="">Any</option>
                        <option value="open"<?= $filters['availability'] === 'open' ? ' selected' : '' ?>>Open to invest</option>
                        <option value="subscribed"<?= $filters['availability'] === 'subscribed' ? ' selected' : '' ?>>Fully subscribed</option>
                    </select>
                </div>

                <?php if (!empty($assetClasses)): ?>
                <div class="filter-group">
                    <label for="f-asset-class">Asset class</label>
                    <select id="f-asset-class" name="asset_class">
                        <option value="">Any asset class</option>
                        <?php foreach ($assetClasses as $family => $classes): ?>
                            <optgroup label="<?= e($family) ?>">
                                <?php foreach ($classes as $ac): ?>
                                    <option value="<?= e($ac['slug']) ?>"<?= $filters['asset_class'] === $ac['slug'] ? ' selected' : '' ?>>
                                        <?= e($ac['name']) ?> (<?= (int) $ac['listing_count'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if (!empty($locations)): ?>
                <div class="filter-group">
                    <label for="f-location">Operating area</label>
                    <select id="f-location" name="location">
                        <option value="">Anywhere</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= e($loc['name']) ?>"<?= $filters['location'] === $loc['name'] ? ' selected' : '' ?>>
                                <?= e($loc['name']) ?> (<?= (int) $loc['listing_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="filter-group">
                    <span class="filter-group-label">NAV per share (R)</span>
                    <div class="range-inputs">
                        <label for="f-min-price" class="sr-only">Minimum NAV per share</label>
                        <input type="number" id="f-min-price" name="min_price" min="0" step="0.01" placeholder="Min" value="<?= e($filters['min_price']) ?>">
                        <span class="muted" aria-hidden="true">&ndash;</span>
                        <label for="f-max-price" class="sr-only">Maximum NAV per share</label>
                        <input type="number" id="f-max-price" name="max_price" min="0" step="0.01" placeholder="Max" value="<?= e($filters['max_price']) ?>">
                    </div>
                </div>

                <div class="add-filter-actions">
                    <button type="submit" class="btn">Apply filters</button>
                    <?php if ($activeFilterCount): ?>
                        <a href="/discover/invest" class="link-button-plain">Clear everything</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
</div>

<!-- ============================================================
     Results
     ============================================================ -->
<div class="results-toolbar">
    <p class="muted results-count">
        <strong><?= number_format((int) $result['total']) ?></strong>
        offering<?= (int) $result['total'] === 1 ? '' : 's' ?><?= $activeSector ? ' in ' . e($activeSector['name']) : '' ?>
    </p>

    <form method="get" action="/discover/invest" class="sort-form">
        <?php $carry(['q', 'sector', 'activity', 'asset_class', 'location', 'min_price', 'max_price', 'availability', 'theme']); ?>
        <label for="f-sort" class="sort-label">Sort by</label>
        <select id="f-sort" name="sort" onchange="this.form.submit()">
            <?php foreach ($sortOptions as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $activeSort === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="btn-compact">Apply</button></noscript>
    </form>
</div>

<?php if (empty($companies)): ?>
    <div class="empty-state">
        <div class="asset-icon"><i class="ti ti-building-off" aria-hidden="true"></i></div>
        <h3>Nothing matches this combination</h3>
        <p class="muted">
            <?php if ($activeSector && $activeFilterCount === 1): ?>
                No live offerings in <?= e($activeSector['name']) ?> right now. The industry is
                covered &mdash; there just isn't an open company in it today.
            <?php else: ?>
                Widen the price range or drop a filter to see more.
            <?php endif; ?>
            <?php if ($activeFilterCount): ?>
                You can also <a href="/discover/invest">clear everything</a> or go back to
                <a href="/discover">Discover</a>.
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>
    <div class="offering-grid">
        <?php foreach ($companies as $c): ?>
            <?php require __DIR__ . '/partials/_offering-card.php'; ?>
        <?php endforeach; ?>
    </div>

    <?php require __DIR__ . '/partials/_pagination.php'; ?>
<?php endif; ?>

<script src="/assets/js/discovery.js" defer></script>
