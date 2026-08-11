<h1>Companies</h1>
<?php if (empty($companies)): ?>
    <p class="muted"><i class="ti ti-building-off" aria-hidden="true"></i> No companies listed yet.</p>
<?php endif; ?>
<div class="card-grid">
<?php foreach ($companies as $c): ?>
    <a href="/company/<?= e($c['reference']) ?>" class="card" style="display:block; text-decoration:none; color:inherit;">
        <div class="card-top">
            <div class="asset-icon"><i class="ti ti-car" aria-hidden="true"></i></div>
            <span class="ref-badge"><?= e($c['reference']) ?></span>
        </div>
        <h3><?= e($c['name']) ?></h3>
        <p class="muted">
            <?php if ($c['location']): ?><i class="ti ti-map-pin" style="font-size:0.85em;" aria-hidden="true"></i><?php endif; ?>
            <?= e(trim(($c['make'] ?? '') . ' ' . ($c['model'] ?? ''))) ?><?= $c['location'] ? ' &middot; ' . e($c['location']) : '' ?>
        </p>
        <p class="muted"><?= e($c['activity_type'] ?? '') ?></p>
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
    </a>
<?php endforeach; ?>
</div>
