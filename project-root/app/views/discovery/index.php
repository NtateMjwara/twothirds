<?php
/**
 * Discovery — the landing page.
 *
 * A directory, not a result set. Search, industries, saved listings, then a
 * two-column band pairing themed collections with the full activity list.
 * Everything here is a way in; the offerings themselves live on /browse.
 *
 * The activity list is deliberately the whole controlled vocabulary rather than
 * only what happens to have a live listing today. Someone looking for cold chain
 * haulage should be able to see the platform covers it and land on an honest
 * empty state, not conclude it doesn't exist.
 */
?>
<section class="discovery-masthead">
    <p class="kicker-sm">Discover</p>
    <h1>Companies that own working assets</h1>
    <p class="muted discovery-lede">
        Every listing is one company, one registered asset, and one commercial activity
        it earns from. Start with an industry, or search for the thing you already have
        in mind.
    </p>

    <form method="get" action="<?= e(invest_url()) ?>" class="discovery-search" role="search">
        <label for="discovery-q" class="sr-only">Search offerings</label>
        <i class="ti ti-search discovery-search-icon" aria-hidden="true"></i>
        <input type="search" id="discovery-q" name="q"
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
            <span class="stat-value"><?= number_format(count($activities)) ?></span>
            <span class="stat-label">Commercial activities</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= number_format(count($locations)) ?></span>
            <span class="stat-label">Operating areas</span>
        </div>
    </div>
</section>

<!-- ============================================================
     Industries
     ============================================================ -->
<section class="rail-section" aria-labelledby="industry-heading">
    <div class="section-head">
        <h2 id="industry-heading">Browse by industry</h2>
        <a class="section-action" href="<?= e(invest_url()) ?>">See every offering</a>
    </div>

    <div class="chip-rail" role="group" aria-label="Industries">
        <?php foreach ($sectors as $s): ?>
            <a class="rail-chip<?= (int) $s['listing_count'] === 0 ? ' is-empty' : '' ?>"
               href="<?= e(invest_url(['sector' => $s['slug']])) ?>" title="<?= e($s['tagline'] ?? '') ?>">
                <span class="rail-chip-icon"><i class="ti <?= e($s['icon']) ?>" aria-hidden="true"></i></span>
                <span class="rail-chip-label"><?= e($s['name']) ?></span>
                <span class="rail-chip-count"><?= number_format((int) $s['listing_count']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============================================================
     Two ways in for people who don't have a listing in mind
     ============================================================ -->
<div class="prompt-row">
    <a class="prompt-card" href="<?= e(invest_url(['theme' => 'newly-listed'])) ?>">
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
     Themes and commercial activities

     Paired deliberately. Themes are curated ways in for people who
     don't know what they want; the activity list is the exhaustive
     index for people who do. Side by side, whichever kind of
     visitor you are, one of the two columns is for you.
     ============================================================ -->
<section class="explore-section">
    <div class="explore-columns">
        <div class="explore-main">
            <div class="section-head">
                <h2 id="theme-heading">Discover by theme</h2>
                <a class="section-action" href="<?= e(invest_url()) ?>">See all offerings</a>
            </div>
            <p class="muted section-sub">Saved searches that cut across every industry.</p>

            <div class="theme-grid" aria-labelledby="theme-heading">
                <?php foreach ($themes as $slug => $theme): ?>
                    <a class="theme-card" href="<?= e(invest_url(['theme' => $slug])) ?>">
                        <span class="theme-art" aria-hidden="true">
                            <i class="ti <?= e($theme['icon']) ?>"></i>
                        </span>
                        <span class="theme-card-body">
                            <span class="theme-head">
                                <span class="theme-label"><?= e($theme['label']) ?></span>
                                <i class="ti ti-arrow-right theme-arrow" aria-hidden="true"></i>
                            </span>
                            <span class="theme-blurb muted"><?= e($theme['blurb']) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <aside class="explore-aside" aria-labelledby="activity-heading">
            <div class="section-head">
                <h2 id="activity-heading">Commercial activities</h2>
            </div>
            <details class="section-explainer">
                <summary><i class="ti ti-info-circle" aria-hidden="true"></i> What are these?</summary>
                <p>
                    A commercial activity is the specific way an asset earns. A minibus on a
                    fixed route earns differently from the same minibus on staff transport,
                    and the risk, the paperwork and the income pattern all follow the activity
                    rather than the vehicle. Every listing is classified by exactly one.
                </p>
            </details>

            <div class="pill-cloud" role="group" aria-label="Commercial activities">
                <?php foreach ($activities as $a): ?>
                    <a class="activity-pill<?= (int) $a['listing_count'] === 0 ? ' is-empty' : '' ?>"
                       href="<?= e(invest_url(['activity' => $a['slug']])) ?>"
                       title="<?= e($a['sector_name']) ?><?= $a['description'] ? ' — ' . e($a['description']) : '' ?>">
                        <span class="pill-icon"><i class="ti <?= e($a['icon'] ?: 'ti-briefcase') ?>" aria-hidden="true"></i></span>
                        <span class="pill-label"><?= e($a['name']) ?></span>
                        <?php if ((int) $a['listing_count'] > 0): ?>
                            <span class="pill-count"><?= number_format((int) $a['listing_count']) ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <p class="muted cloud-note">
                Activities without a number have no live offering right now. They're listed
                because the platform covers them &mdash; check back rather than assuming
                nothing ever trades there.
            </p>
        </aside>
    </div>
</section>

<script src="/assets/js/discovery.js" defer></script>
