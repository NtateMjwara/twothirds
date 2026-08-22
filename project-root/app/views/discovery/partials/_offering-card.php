<?php
/**
 * One listing in the discovery grid.
 *
 * Expects: $c (listing row), $watchedIds (company id => anything), $returnTo (string).
 * Optional: $c['cover_path'] - a photograph of the asset, used as the card's
 * hero. Where there isn't one the header falls back to a graphite panel with the
 * asset-class icon, so the grid stays even rather than some cards being shorter
 * than others.
 * The save control is a form rather than a link because it writes, and it sits
 * outside the card's own <a> because a button inside a link is invalid markup
 * and unusable with a keyboard.
 */
$funded = (int) ($c['funded_pct'] ?? 0);
$available = (int) ($c['shares_available'] ?? 0);
$isWatched = isset($watchedIds[(int) $c['id']]);
$vehicle = trim(((string) ($c['make'] ?? '')) . ' ' . ((string) ($c['model'] ?? '')));
$signedIn = !empty($_SESSION['user_id']);
?>
<article class="offering-card<?= $available === 0 ? ' is-subscribed' : '' ?>">
    <a href="<?= e(company_url($c)) ?>" class="offering-card-link">
        <div class="offering-cover<?= empty($c['cover_path']) ? ' is-empty' : '' ?>">
            <?php if (!empty($c['cover_path'])): ?>
                <img src="/uploads/assets/<?= e($c['cover_path']) ?>"
                     alt="<?= e($c['cover_caption'] ?: ($vehicle !== '' ? $vehicle : $c['name'])) ?>"
                     loading="lazy" width="400" height="220">
            <?php else: ?>
                <span class="cover-fallback" aria-hidden="true">
                    <i class="ti <?= e($c['asset_icon'] ?: 'ti-car') ?>"></i>
                </span>
            <?php endif; ?>

            <span class="ref-badge cover-ref"><?= e($c['reference']) ?></span>

            <?php if (!empty($c['sector_name'])): ?>
                <span class="cover-sector">
                    <i class="ti <?= e($c['sector_icon'] ?: 'ti-briefcase') ?>" aria-hidden="true"></i>
                    <?= e($c['sector_name']) ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="offering-card-body">

        <h3 class="offering-name"><?= e($c['name']) ?></h3>

        <?php $activity = $c['activity_name'] ?: $c['activity_label']; ?>
        <?php if ($activity): ?>
            <span class="category-tag"><?= e($activity) ?></span>
        <?php endif; ?>

        <p class="offering-meta muted">
            <?php if ($vehicle !== ''): ?>
                <span><?= e($vehicle) ?><?= $c['year'] ? ' &middot; ' . (int) $c['year'] : '' ?></span>
            <?php endif; ?>
            <?php if (!empty($c['asset_class_name'])): ?>
                <span class="offering-meta-sep">&middot;</span><span><?= e($c['asset_class_name']) ?></span>
            <?php endif; ?>
            <?php if (!empty($c['location'])): ?>
                <span class="offering-meta-sep">&middot;</span>
                <span><i class="ti ti-map-pin" aria-hidden="true"></i> <?= e($c['location']) ?></span>
            <?php endif; ?>
        </p>

        <div class="offering-stats">
            <div class="stat">
                <span class="stat-value">R<?= number_format((float) $c['nav_per_share'], 2) ?></span>
                <span class="stat-label">NAV / share</span>
            </div>
            <div class="stat">
                <span class="stat-value"><?= number_format($available) ?></span>
                <span class="stat-label">Shares available</span>
            </div>
            <?php if ($c['utilisation_rate'] !== null && $c['utilisation_rate'] !== ''): ?>
            <div class="stat">
                <span class="stat-value"><?= number_format((float) $c['utilisation_rate'], 0) ?>%</span>
                <span class="stat-label">Utilisation</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="funded-bar" role="progressbar" aria-valuenow="<?= $funded ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Subscribed">
            <div class="funded-bar-fill" style="width:<?= $funded ?>%;"></div>
        </div>
        <p class="funded-label muted">
            <?= $available === 0 ? 'Fully subscribed' : $funded . '% subscribed' ?>
            <?php if (!empty($c['period_count'])): ?>
                <span class="offering-flag"><i class="ti ti-report-money" aria-hidden="true"></i> Trading</span>
            <?php endif; ?>
        </p>
        </div>
    </a>

    <?php if ($signedIn): ?>
        <form method="post" action="/watchlist/<?= e($c['reference']) ?>/toggle" class="offering-save">
            <?= csrf_field() ?>
            <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
            <button type="submit"
                    class="save-toggle<?= $isWatched ? ' is-saved' : '' ?>"
                    aria-pressed="<?= $isWatched ? 'true' : 'false' ?>"
                    title="<?= $isWatched ? 'Remove from watchlist' : 'Save to watchlist' ?>">
                <i class="ti <?= $isWatched ? 'ti-bookmark-filled' : 'ti-bookmark' ?>" aria-hidden="true"></i>
                <span class="sr-only"><?= $isWatched ? 'Remove ' . e($c['name']) . ' from watchlist' : 'Save ' . e($c['name']) . ' to watchlist' ?></span>
            </button>
        </form>
    <?php else: ?>
        <a class="save-toggle save-toggle-guest" href="/login" title="Log in to save this listing">
            <i class="ti ti-bookmark" aria-hidden="true"></i>
            <span class="sr-only">Log in to save <?= e($c['name']) ?></span>
        </a>
    <?php endif; ?>
</article>
