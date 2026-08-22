<?php
/**
 * Watchlist.
 *
 * No section navigation of its own. Everything that partial listed now lives
 * behind the avatar in the header, and repeating it here meant two navigations
 * on one page disagreeing about which one you were meant to use.
 *
 * The cards are the same partial the discovery grid uses. The watchlist and the
 * results grid show the same thing, so they should look the same and stay in
 * step - the page previously grew its own cut-down card that then drifted.
 */
$returnTo = '/account/watchlist';

// The save control on every card is a "remove" here, since being on this page
// means being watched.
$watchedIds = [];
foreach ($watchlist as $row) {
    $watchedIds[(int) $row['id']] = true;
}

$openCount = 0;
$closingCount = 0;
foreach ($watchlist as $row) {
    if ((int) $row['shares_available'] > 0) {
        $openCount++;
        if ((int) $row['funded_pct'] >= 75) {
            $closingCount++;
        }
    }
}
?>
<div class="watchlist-head">
    <div>
        <h1>Your watchlist</h1>
        <p class="muted">
            <?php if ($watchlist): ?>
                <?= count($watchlist) ?> saved
                &middot; <?= $openCount ?> still open to invest
            <?php else: ?>
                Companies you save are kept here so you can follow them without committing.
            <?php endif; ?>
        </p>
    </div>

    <?php if ($watchlist): ?>
        <a href="<?= e(invest_url()) ?>" class="btn-outline"><i class="ti ti-search" aria-hidden="true"></i> Find more</a>
    <?php endif; ?>
</div>

<?php if ($closingCount > 0): ?>
    <!-- Worth surfacing: the whole point of saving something is not to miss it,
         and "75% subscribed" is the last useful moment to notice. -->
    <p class="notice notice-warn watchlist-alert">
        <i class="ti ti-flame" aria-hidden="true"></i>
        <?= $closingCount === 1 ? 'One saved company is' : $closingCount . ' saved companies are' ?>
        more than 75% subscribed.
    </p>
<?php endif; ?>

<?php if (empty($watchlist)): ?>
    <div class="empty-state watchlist-empty">
        <div class="asset-icon"><i class="ti ti-bookmark" aria-hidden="true"></i></div>
        <h2>Nothing saved yet</h2>
        <p class="muted">
            Tap the bookmark on any offering to keep it here. Saving costs nothing and commits
            you to nothing &mdash; it's a way of watching how a company trades before deciding.
        </p>
        <p><a href="<?= e(invest_url()) ?>" class="btn"><i class="ti ti-search" aria-hidden="true"></i> Browse offerings</a></p>
    </div>
<?php else: ?>
    <div class="offering-grid">
        <?php foreach ($watchlist as $c): ?>
            <?php require __DIR__ . '/../discovery/partials/_offering-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
