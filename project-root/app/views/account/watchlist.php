<?php require __DIR__ . '/../partials/account-nav.php'; ?>
<h1>Watchlist</h1>
<?php if (empty($watchlist)): ?>
    <p class="muted"><i class="ti ti-bookmark-off" aria-hidden="true"></i> No companies saved yet.</p>
<?php else: ?>
<div class="card-grid">
<?php foreach ($watchlist as $w): ?>
    <a href="/company/<?= e($w['reference']) ?>" class="card" style="display:block; text-decoration:none; color:inherit;">
        <div class="card-top">
            <div class="asset-icon"><i class="ti ti-car" aria-hidden="true"></i></div>
            <span class="ref-badge"><?= e($w['reference']) ?></span>
        </div>
        <h3><?= e($w['name']) ?></h3>
        <div class="stat">
            <span class="stat-value">R<?= number_format((float) $w['nav_per_share'], 2) ?></span>
            <span class="stat-label">NAV/share</span>
        </div>
    </a>
<?php endforeach; ?>
</div>
<?php endif; ?>
