<?php
$sortOptions = [
    'newest' => 'Newest listings',
    'price_asc' => 'NAV: low to high',
    'price_desc' => 'NAV: high to low',
    'available_desc' => 'Most shares available',
];
$activeSort = $filters['sort'] !== '' ? $filters['sort'] : 'newest';
// 'sort' rearranges results, it doesn't narrow them - so it doesn't count as an
// active "filter" for the toggle badge, the chip row, or the clear-all prompt.
$activeFilterCount = count(array_filter(
    array_diff_key($filters, ['sort' => true]),
    fn ($v) => $v !== ''
));
?>
<section class="discovery-hero">
    <div>
        <p class="kicker-sm">Discover</p>
        <h1>Live investment offerings</h1>
        <p class="muted discovery-hero-lede">
            Every company below owns a real, income-producing asset. Filter by category, location,
            asset type and price to find the offering that fits your portfolio.
        </p>
    </div>
    <div class="stat-row discovery-hero-stats">
        <div class="stat">
            <span class="stat-value"><?= number_format($totalActive) ?></span>
            <span class="stat-label">Live offerings</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= number_format(count($facets['categories'])) ?></span>
            <span class="stat-label">Categories</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= number_format(count($facets['locations'])) ?></span>
            <span class="stat-label">Locations</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= number_format(count($facets['assetTypes'])) ?></span>
            <span class="stat-label">Asset types</span>
        </div>
    </div>
</section>

<button type="button" class="btn-outline discovery-filter-toggle" id="discoveryFilterToggle" aria-expanded="false" aria-controls="discoveryFilters">
    <i class="ti ti-adjustments-horizontal" aria-hidden="true"></i> Filters<?= $activeFilterCount ? " ({$activeFilterCount})" : '' ?>
</button>

<div class="discovery-layout">
    <aside class="discovery-filters" id="discoveryFilters">
        <form method="get" action="/discover" class="filter-form">
            <div class="filter-group">
                <label for="f-q">Search</label>
                <input type="text" id="f-q" name="q" value="<?= e($filters['q']) ?>" placeholder="Company, make or model">
            </div>

            <?php if (!empty($facets['categories'])): ?>
            <div class="filter-group">
                <span class="filter-group-label">Category</span>
                <div class="pill-group">
                    <a href="/discover<?= e(query_with($filters, ['category' => null])) ?>" class="pill<?= $filters['category'] === '' ? ' active' : '' ?>">All</a>
                    <?php foreach ($facets['categories'] as $cat): ?>
                        <a href="/discover<?= e(query_with($filters, ['category' => $cat])) ?>" class="pill<?= $filters['category'] === $cat ? ' active' : '' ?>"><?= e($cat) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($facets['locations'])): ?>
            <div class="filter-group">
                <label for="f-location">Location</label>
                <select id="f-location" name="location">
                    <option value="">All locations</option>
                    <?php foreach ($facets['locations'] as $loc): ?>
                        <option value="<?= e($loc) ?>"<?= $filters['location'] === $loc ? ' selected' : '' ?>><?= e($loc) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!empty($facets['assetTypes'])): ?>
            <div class="filter-group">
                <label for="f-asset-type">Asset type</label>
                <select id="f-asset-type" name="asset_type">
                    <option value="">All asset types</option>
                    <?php foreach ($facets['assetTypes'] as $type): ?>
                        <option value="<?= e($type) ?>"<?= $filters['asset_type'] === $type ? ' selected' : '' ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="filter-group">
                <span class="filter-group-label">NAV per share (R)</span>
                <div class="range-inputs">
                    <input type="number" name="min_price" min="0" step="0.01" placeholder="Min" value="<?= e($filters['min_price']) ?>">
                    <span class="muted">&ndash;</span>
                    <input type="number" name="max_price" min="0" step="0.01" placeholder="Max" value="<?= e($filters['max_price']) ?>">
                </div>
            </div>

            <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">

            <div class="filter-actions">
                <button type="submit" class="btn"><i class="ti ti-filter" aria-hidden="true"></i> Apply filters</button>
                <?php if ($activeFilterCount): ?>
                    <a href="/discover" class="link-button-plain">Clear all</a>
                <?php endif; ?>
            </div>
        </form>
    </aside>

    <div class="discovery-results">
        <div class="discovery-toolbar">
            <p class="muted discovery-count">
                Showing <strong><?= number_format(count($companies)) ?></strong> of <?= number_format($totalActive) ?> offering<?= $totalActive === 1 ? '' : 's' ?>
            </p>
            <form method="get" action="/discover" class="sort-form">
                <?php foreach ($filters as $key => $value): ?>
                    <?php if ($key === 'sort' || $value === '') continue; ?>
                    <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
                <?php endforeach; ?>
                <label for="f-sort" class="sr-only">Sort by</label>
                <select id="f-sort" name="sort" onchange="this.form.submit()">
                    <?php foreach ($sortOptions as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $activeSort === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($activeFilterCount): ?>
        <div class="active-filter-chips">
            <?php if ($filters['q'] !== ''): ?>
                <a class="chip" href="/discover<?= e(query_with($filters, ['q' => null])) ?>">&ldquo;<?= e($filters['q']) ?>&rdquo; <i class="ti ti-x" aria-hidden="true"></i></a>
            <?php endif; ?>
            <?php if ($filters['category'] !== ''): ?>
                <a class="chip" href="/discover<?= e(query_with($filters, ['category' => null])) ?>"><?= e($filters['category']) ?> <i class="ti ti-x" aria-hidden="true"></i></a>
            <?php endif; ?>
            <?php if ($filters['location'] !== ''): ?>
                <a class="chip" href="/discover<?= e(query_with($filters, ['location' => null])) ?>"><i class="ti ti-map-pin" aria-hidden="true"></i> <?= e($filters['location']) ?> <i class="ti ti-x" aria-hidden="true"></i></a>
            <?php endif; ?>
            <?php if ($filters['asset_type'] !== ''): ?>
                <a class="chip" href="/discover<?= e(query_with($filters, ['asset_type' => null])) ?>"><?= e($filters['asset_type']) ?> <i class="ti ti-x" aria-hidden="true"></i></a>
            <?php endif; ?>
            <?php if ($filters['min_price'] !== '' || $filters['max_price'] !== ''): ?>
                <a class="chip" href="/discover<?= e(query_with($filters, ['min_price' => null, 'max_price' => null])) ?>">
                    R<?= e($filters['min_price'] !== '' ? $filters['min_price'] : '0') ?>&ndash;<?= $filters['max_price'] !== '' ? 'R' . e($filters['max_price']) : '&infin;' ?> <i class="ti ti-x" aria-hidden="true"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($companies)): ?>
            <div class="empty-state">
                <div class="asset-icon"><i class="ti ti-building-off" aria-hidden="true"></i></div>
                <h3>No offerings match your filters</h3>
                <p class="muted">Try widening your search, or <a href="/discover">clear all filters</a> to see everything live.</p>
            </div>
        <?php else: ?>
        <div class="card-grid discovery-grid">
        <?php foreach ($companies as $c):
            $funded = $c['shares_issued'] > 0
                ? (($c['shares_issued'] - $c['shares_available']) / $c['shares_issued']) * 100
                : 0;
            $funded = (int) round(min(100, max(0, $funded)));
        ?>
            <a href="/company/<?= e($c['reference']) ?>" class="card discovery-card">
                <div class="card-top">
                    <div class="asset-icon"><i class="ti ti-car" aria-hidden="true"></i></div>
                    <span class="ref-badge"><?= e($c['reference']) ?></span>
                </div>
                <?php if (!empty($c['activity_type'])): ?>
                    <span class="category-tag"><?= e($c['activity_type']) ?></span>
                <?php endif; ?>
                <h3><?= e($c['name']) ?></h3>
                <p class="muted">
                    <?php if ($c['location']): ?><i class="ti ti-map-pin" style="font-size:0.85em;" aria-hidden="true"></i><?php endif; ?>
                    <?= e(trim(($c['make'] ?? '') . ' ' . ($c['model'] ?? ''))) ?><?= $c['location'] ? ' &middot; ' . e($c['location']) : '' ?>
                </p>
                <div class="card-stats">
                    <div class="stat">
                        <span class="stat-value">R<?= number_format((float) $c['nav_per_share'], 2) ?></span>
                        <span class="stat-label">NAV/share</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value"><?= number_format((int) $c['shares_available']) ?></span>
                        <span class="stat-label">Available</span>
                    </div>
                </div>
                <div class="funded-bar" role="progressbar" aria-valuenow="<?= $funded ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Subscribed">
                    <div class="funded-bar-fill" style="width:<?= $funded ?>%;"></div>
                </div>
                <p class="funded-label muted"><?= $funded ?>% subscribed</p>
            </a>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="/assets/js/discovery-filters.js"></script>
