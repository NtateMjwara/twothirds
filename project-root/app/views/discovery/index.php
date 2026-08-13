<?php
/**
 * Discovery.
 *
 * Reads top to bottom as a narrowing funnel: search, then industry, then the
 * commercial activity inside that industry, then the specifics. Everything above
 * the results block is navigation and writes to the same query string the
 * results block reads, so any state on this page is a URL someone can send to
 * a friend.
 */
$activeSort = $filters['sort'] !== '' ? $filters['sort'] : 'newest';

// Sort rearranges results, it doesn't narrow them, so it isn't counted as an
// active filter for the badge, the chip row, or the clear-all prompt.
$activeFilterCount = count(array_filter(
    array_diff_key($filters, ['sort' => true]),
    static fn ($v) => $v !== ''
));

$returnTo = '/discover' . query_with($filters, ['page' => $result['page']]);

// Reprints the filter state a link-driven control owns, so a form submission
// somewhere else on the page doesn't silently drop it.
$carry = static function (array $keys) use ($filters): void {
    foreach ($keys as $key) {
        if (($filters[$key] ?? '') !== '') {
            echo '<input type="hidden" name="' . e($key) . '" value="' . e($filters[$key]) . '">' . "\n";
        }
    }
};

$sectorHeading = $activeSector['name'] ?? 'All industries';
?>

<section class="discovery-masthead">
    <p class="kicker-sm">Discover</p>
    <h1>Companies that own working assets</h1>
    <p class="muted discovery-lede">
        Every listing is one company, one registered asset, and one commercial activity
        it earns from. Start with an industry, or search for the thing you already have in mind.
    </p>

    <form method="get" action="/discover" class="discovery-search" role="search">
        <?php $carry(['sector', 'activity', 'asset_class', 'location', 'min_price', 'max_price', 'availability', 'theme', 'sort']); ?>
        <label for="discovery-q" class="sr-only">Search offerings</label>
        <i class="ti ti-search discovery-search-icon" aria-hidden="true"></i>
        <input type="search" id="discovery-q" name="q" value="<?= e($filters['q']) ?>"
               placeholder="Search by company, reference, vehicle, activity or town"
               autocomplete="off">
        <button type="submit" class="btn discovery-search-btn">Search</button>
    </form>

    <div class="stat-row discovery-stats">
        <div class="stat">
            <span class="stat-value"><?= number_format($totalActive) ?></span>
            <span class="stat-label">Live offerings</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= number_format(count($sectors)) ?></span>
            <span class="stat-label">Industries</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= number_format(count($locations)) ?></span>
            <span class="stat-label">Operating areas</span>
        </div>
    </div>
</section>

<!-- ============================================================
     Industry rail - the top-level cut, always visible
     ============================================================ -->
<section class="rail-section" aria-labelledby="industry-heading">
    <div class="section-head">
        <h2 id="industry-heading">Filter by industry</h2>
        <?php if ($filters['sector'] !== ''): ?>
            <a class="section-action" href="/discover<?= e(query_with($filters, ['sector' => null, 'activity' => null, 'page' => null])) ?>">Clear industry</a>
        <?php endif; ?>
    </div>

    <div class="chip-rail" role="group" aria-label="Industries">
        <a class="rail-chip<?= $filters['sector'] === '' ? ' is-active' : '' ?>"
           href="/discover<?= e(query_with($filters, ['sector' => null, 'activity' => null, 'page' => null])) ?>">
            <span class="rail-chip-icon"><i class="ti ti-layout-grid" aria-hidden="true"></i></span>
            <span class="rail-chip-label">All</span>
            <span class="rail-chip-count"><?= number_format($totalActive) ?></span>
        </a>
        <?php foreach ($sectors as $s): ?>
            <a class="rail-chip<?= $filters['sector'] === $s['slug'] ? ' is-active' : '' ?><?= (int) $s['listing_count'] === 0 ? ' is-empty' : '' ?>"
               href="/discover<?= e(query_with($filters, ['sector' => $s['slug'], 'activity' => null, 'page' => null])) ?>"
               title="<?= e($s['tagline'] ?? '') ?>">
                <span class="rail-chip-icon"><i class="ti <?= e($s['icon']) ?>" aria-hidden="true"></i></span>
                <span class="rail-chip-label"><?= e($s['name']) ?></span>
                <span class="rail-chip-count"><?= number_format((int) $s['listing_count']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($activeSector && !empty($activeSector['tagline'])): ?>
        <p class="rail-note muted"><i class="ti <?= e($activeSector['icon']) ?>" aria-hidden="true"></i> <?= e($activeSector['tagline']) ?></p>
    <?php endif; ?>
</section>

<!-- ============================================================
     Availability toggle
     ============================================================ -->
<div class="segmented" role="group" aria-label="Availability">
    <?php
    $segments = [
        ''           => 'All offerings',
        'open'       => 'Open to invest',
        'subscribed' => 'Fully subscribed',
    ];
    ?>
    <?php foreach ($segments as $value => $label): ?>
        <a class="segment<?= $filters['availability'] === $value ? ' is-active' : '' ?>"
           href="/discover<?= e(query_with($filters, ['availability' => $value ?: null, 'page' => null])) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<!-- ============================================================
     Commercial activities - the second cut, scoped to the industry
     ============================================================ -->
<?php if (!empty($activities)): ?>
<section class="tile-section" aria-labelledby="activity-heading">
    <div class="section-head">
        <h2 id="activity-heading">
            <?= $activeSector ? e($activeSector['name']) . ' &mdash; activities' : 'Commercial activities' ?>
        </h2>
        <details class="section-explainer">
            <summary><i class="ti ti-info-circle" aria-hidden="true"></i> What are these?</summary>
            <p>
                A commercial activity is the specific way an asset earns: a minibus on a fixed
                route earns differently from the same minibus on staff transport, and the risk,
                the paperwork and the income pattern all follow the activity rather than the
                vehicle. Every listing is classified by exactly one.
            </p>
        </details>
    </div>

    <div class="tile-grid">
        <?php foreach ($activities as $a): ?>
            <a class="tile<?= $filters['activity'] === $a['slug'] ? ' is-active' : '' ?><?= (int) $a['listing_count'] === 0 ? ' is-empty' : '' ?>"
               href="/discover<?= e(query_with($filters, ['activity' => $a['slug'], 'page' => null])) ?>">
                <span class="tile-icon"><i class="ti <?= e($a['icon'] ?: 'ti-briefcase') ?>" aria-hidden="true"></i></span>
                <span class="tile-label"><?= e($a['name']) ?></span>
                <span class="tile-count"><?= number_format((int) $a['listing_count']) ?> live</span>
                <?php if ($filters['sector'] === '' && !empty($a['sector_name'])): ?>
                    <span class="tile-sector"><?= e($a['sector_name']) ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($filters['sector'] === ''): ?>
        <p class="tile-note muted">Showing the busiest activities. Pick an industry above to see the full list for it.</p>
    <?php endif; ?>
</section>
<?php endif; ?>

<!-- ============================================================
     Two ways in for people who don't have a listing in mind
     ============================================================ -->
<div class="prompt-row">
    <a class="prompt-card" href="/discover<?= e(query_with([], ['theme' => 'newly-listed'])) ?>">
        <span class="prompt-icon"><i class="ti ti-compass" aria-hidden="true"></i></span>
        <span class="prompt-body">
            <span class="prompt-title">Not sure where to start?</span>
            <span class="prompt-text muted">See what opened in the last month, then work backwards to the industry.</span>
        </span>
        <i class="ti ti-chevron-right prompt-chevron" aria-hidden="true"></i>
    </a>
    <a class="prompt-card" href="/how-it-works">
        <span class="prompt-icon"><i class="ti ti-book-2" aria-hidden="true"></i></span>
        <span class="prompt-body">
            <span class="prompt-title">How ownership works here</span>
            <span class="prompt-text muted">Shares, NAV, the asset replacement fund, and what a commitment actually commits you to.</span>
        </span>
        <i class="ti ti-chevron-right prompt-chevron" aria-hidden="true"></i>
    </a>
</div>

<!-- ============================================================
     Watchlist snapshots
     ============================================================ -->
<?php if (!empty($watchlist)): ?>
<section class="rail-section" aria-labelledby="watchlist-heading">
    <div class="section-head">
        <h2 id="watchlist-heading">
            <i class="ti <?= $watchlistIsOwn ? 'ti-bookmark-filled' : 'ti-users' ?> section-head-icon" aria-hidden="true"></i>
            <?= $watchlistIsOwn ? 'Your watchlist' : 'Most watched' ?>
        </h2>
        <?php if ($watchlistIsOwn): ?>
            <a class="section-action" href="/account/watchlist">See all</a>
        <?php elseif (empty($_SESSION['user_id'])): ?>
            <a class="section-action" href="/register">Start your own</a>
        <?php endif; ?>
    </div>

    <div class="snapshot-rail">
        <?php foreach ($watchlist as $w): ?>
            <?php require __DIR__ . '/partials/_snapshot-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     Themes
     ============================================================ -->
<section class="rail-section" aria-labelledby="theme-heading">
    <div class="section-head">
        <h2 id="theme-heading">Discover by theme</h2>
        <?php if ($filters['theme'] !== ''): ?>
            <a class="section-action" href="/discover<?= e(query_with($filters, ['theme' => null, 'page' => null])) ?>">Clear theme</a>
        <?php endif; ?>
    </div>

    <div class="theme-rail">
        <?php foreach ($themes as $slug => $theme): ?>
            <a class="theme-card<?= $filters['theme'] === $slug ? ' is-active' : '' ?>"
               href="/discover<?= e(query_with($filters, ['theme' => $filters['theme'] === $slug ? null : $slug, 'page' => null])) ?>">
                <i class="ti <?= e($theme['icon']) ?>" aria-hidden="true"></i>
                <span class="theme-label"><?= e($theme['label']) ?></span>
                <span class="theme-blurb muted"><?= e($theme['blurb']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============================================================
     Results
     ============================================================ -->
<section class="results-section" aria-labelledby="results-heading">
    <div class="results-head">
        <h2 id="results-heading"><?= e($sectorHeading) ?></h2>
        <p class="muted results-count">
            <strong><?= number_format((int) $result['total']) ?></strong>
            offering<?= (int) $result['total'] === 1 ? '' : 's' ?> match<?= (int) $result['total'] === 1 ? 'es' : '' ?> your view
        </p>
    </div>

    <button type="button" class="btn-outline filter-drawer-toggle" id="filterDrawerToggle"
            aria-expanded="false" aria-controls="discoveryFilters">
        <i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>
        Refine<?= $activeFilterCount ? " ({$activeFilterCount})" : '' ?>
    </button>

    <div class="discovery-layout">
        <aside class="discovery-filters" id="discoveryFilters">
            <form method="get" action="/discover" class="filter-form">
                <?php $carry(['q', 'sector', 'activity', 'availability', 'theme', 'sort']); ?>

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

                <div class="filter-actions">
                    <button type="submit" class="btn"><i class="ti ti-filter" aria-hidden="true"></i> Apply</button>
                    <?php if ($activeFilterCount): ?>
                        <a href="/discover" class="link-button-plain">Clear everything</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="filter-aside">
                <p class="filter-aside-title">Reference</p>
                <p class="muted">
                    NAV per share is the company's net asset value divided by shares issued.
                    Utilisation is the share of available operating time the asset was actually earning
                    in its most recent reported period.
                </p>
            </div>
        </aside>

        <div class="discovery-results">
            <div class="discovery-toolbar">
                <?php if ($activeFilterCount): ?>
                <div class="active-filter-chips">
                    <?php if ($filters['q'] !== ''): ?>
                        <a class="chip" href="/discover<?= e(query_with($filters, ['q' => null, 'page' => null])) ?>">
                            &ldquo;<?= e($filters['q']) ?>&rdquo; <i class="ti ti-x" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($activeSector): ?>
                        <a class="chip" href="/discover<?= e(query_with($filters, ['sector' => null, 'activity' => null, 'page' => null])) ?>">
                            <i class="ti <?= e($activeSector['icon']) ?>" aria-hidden="true"></i> <?= e($activeSector['name']) ?> <i class="ti ti-x" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($filters['activity'] !== ''): ?>
                        <a class="chip" href="/discover<?= e(query_with($filters, ['activity' => null, 'page' => null])) ?>">
                            <?= e($filters['activity']) ?> <i class="ti ti-x" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($filters['asset_class'] !== ''): ?>
                        <a class="chip" href="/discover<?= e(query_with($filters, ['asset_class' => null, 'page' => null])) ?>">
                            <?= e($filters['asset_class']) ?> <i class="ti ti-x" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($filters['location'] !== ''): ?>
                        <a class="chip" href="/discover<?= e(query_with($filters, ['location' => null, 'page' => null])) ?>">
                            <i class="ti ti-map-pin" aria-hidden="true"></i> <?= e($filters['location']) ?> <i class="ti ti-x" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($filters['theme'] !== '' && isset($themes[$filters['theme']])): ?>
                        <a class="chip" href="/discover<?= e(query_with($filters, ['theme' => null, 'page' => null])) ?>">
                            <?= e($themes[$filters['theme']]['label']) ?> <i class="ti ti-x" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($filters['availability'] !== ''): ?>
                        <a class="chip" href="/discover<?= e(query_with($filters, ['availability' => null, 'page' => null])) ?>">
                            <?= $filters['availability'] === 'open' ? 'Open to invest' : 'Fully subscribed' ?> <i class="ti ti-x" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($filters['min_price'] !== '' || $filters['max_price'] !== ''): ?>
                        <a class="chip" href="/discover<?= e(query_with($filters, ['min_price' => null, 'max_price' => null, 'page' => null])) ?>">
                            R<?= e($filters['min_price'] !== '' ? $filters['min_price'] : '0') ?>&ndash;<?= $filters['max_price'] !== '' ? 'R' . e($filters['max_price']) : '&infin;' ?>
                            <i class="ti ti-x" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <form method="get" action="/discover" class="sort-form">
                    <?php $carry(['q', 'sector', 'activity', 'asset_class', 'location', 'min_price', 'max_price', 'availability', 'theme']); ?>
                    <label for="f-sort" class="sort-label">Sort</label>
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
                        Widen the price range or drop a filter to see more.
                        <?php if ($activeFilterCount): ?>
                            You can also <a href="/discover">clear everything</a> and start again.
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
        </div>
    </div>
</section>

<script src="/assets/js/discovery.js" defer></script>
