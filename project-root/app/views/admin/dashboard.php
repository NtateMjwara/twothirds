<?php
/**
 * Admin dashboard.
 *
 * Tiles are filtered by permission, so an ops account doesn't see a settlement
 * link that 403s and a finance account doesn't see KYC.
 */
use app\services\AdminPolicy;
$role = $_SESSION['admin_role'] ?? null;
$can = static fn (string $permission): bool => AdminPolicy::can($role, $permission);

$tiles = [
    ['company.view',      '/admin/companies',        'ti-building',    'View companies'],
    ['company.manage',    '/admin/companies/create', 'ti-plus',        'Create a new SPV'],
    ['settlement.view',   '/admin/settlement',       'ti-cash',        'Settlement queue'],
    ['kyc.view',          '/admin/kyc',              'ti-shield-check','KYC review'],
    ['email.view',        '/admin/email-queue',      'ti-mail',        'Email queue'],
    ['registry.view',     '/admin/registry',         'ti-list-details','Shareholder registry'],
    ['investor.view',     '/admin/investors',        'ti-users',       'Investors'],
];
?>
<div class="page-title-row">
    <h1>Dashboard</h1>
</div>

<div class="stat-row">
    <div class="stat"><span class="stat-value"><i class="ti ti-building-bank" aria-hidden="true"></i> R<?= number_format($stats['aum'], 0) ?></span><span class="stat-label">Total AUM</span></div>
    <div class="stat"><span class="stat-value"><i class="ti ti-building" aria-hidden="true"></i> <?= number_format($stats['companyCount']) ?></span><span class="stat-label">Active companies</span></div>
    <div class="stat"><span class="stat-value"><i class="ti ti-users" aria-hidden="true"></i> <?= number_format($stats['shareholders']) ?></span><span class="stat-label">Shareholders</span></div>
    <div class="stat"><span class="stat-value"><i class="ti ti-clock" aria-hidden="true"></i> <?= number_format($stats['pendingCount']) ?></span><span class="stat-label">Pending commitments</span></div>
    <div class="stat"><span class="stat-value"><i class="ti ti-hourglass" aria-hidden="true"></i> R<?= number_format($stats['pendingValue'], 0) ?></span><span class="stat-label">Pending value</span></div>
    <div class="stat"><span class="stat-value"><i class="ti ti-shield-check" aria-hidden="true"></i> R<?= number_format($stats['totalArfBalance'], 0) ?></span><span class="stat-label">Total ARF reserves</span></div>
</div>

<?php if (!empty($queues)): ?>
<h2>Waiting on you</h2>
<div class="card-grid">
    <?php foreach ($queues as $queue): ?>
        <a href="<?= e($queue['href']) ?>" class="card admin-tile">
            <i class="ti <?= e($queue['icon']) ?>" aria-hidden="true"></i>
            <?= e($queue['label']) ?>
            <?php if ($queue['count'] > 0): ?>
                <span class="ref-badge" style="margin-left:auto;"><?= number_format($queue['count']) ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<h2>Quick actions</h2>
<div class="card-grid">
    <?php foreach ($tiles as [$permission, $href, $icon, $label]): ?>
        <?php if ($can($permission)): ?>
            <a href="<?= e($href) ?>" class="card admin-tile">
                <i class="ti <?= e($icon) ?>" aria-hidden="true"></i> <?= e($label) ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
