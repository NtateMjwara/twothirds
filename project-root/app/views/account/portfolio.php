<?php require __DIR__ . '/../partials/account-nav.php'; ?>
<h1>Your portfolio</h1>
<p class="muted">Logged in as <?= e($_SESSION['user_name']) ?></p>

<?php
$totalRegisteredValue = 0;
foreach ($holdings as $h) { $totalRegisteredValue += $h['shares'] * $h['nav_per_share']; }
$totalPendingValue = 0;
foreach ($pending as $p) { $totalPendingValue += $p['shares_requested'] * $p['nav_per_share']; }
?>

<div class="stat-row">
    <div class="stat">
        <span class="stat-value">R<?= number_format($totalRegisteredValue, 0) ?></span>
        <span class="stat-label">Registered value</span>
    </div>
    <div class="stat">
        <span class="stat-value">R<?= number_format($totalPendingValue, 0) ?></span>
        <span class="stat-label">Pending value</span>
    </div>
    <div class="stat">
        <span class="stat-value"><?= count($holdings) ?></span>
        <span class="stat-label">Holdings</span>
    </div>
</div>

<h2>Registered</h2>
<?php if (empty($holdings)): ?>
    <p class="muted">No registered holdings yet.</p>
<?php else: ?>
<table>
    <tr><th>Company</th><th>Shares</th><th>Value</th><th>Settled</th></tr>
    <?php foreach ($holdings as $h): ?>
    <tr>
        <td>
            <a href="/company/<?= e($h['company_reference']) ?>"><?= e($h['company_name']) ?></a>
            <span class="ref-badge"><?= e($h['company_reference']) ?></span>
        </td>
        <td><?= number_format((int) $h['shares']) ?></td>
        <td>R<?= number_format($h['shares'] * $h['nav_per_share'], 2) ?></td>
        <td><?= e($h['settled_at']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<h2>Pending</h2>
<?php if (empty($pending)): ?>
    <p class="muted">No pending commitments.</p>
<?php else: ?>
<table>
    <tr><th>Reference</th><th>Company</th><th>Shares</th><th>Value</th><th>Expires</th><th></th></tr>
    <?php foreach ($pending as $p): ?>
    <tr>
        <td><span class="ref-badge"><?= e($p['reference']) ?></span></td>
        <td><a href="/company/<?= e($p['company_reference']) ?>"><?= e($p['company_name']) ?></a></td>
        <td><?= number_format((int) $p['shares_requested']) ?></td>
        <td>R<?= number_format($p['shares_requested'] * $p['nav_per_share'], 2) ?></td>
        <td><?= e($p['expires_at']) ?></td>
        <td>
            <form method="post" action="/account/commitments/<?= (int) $p['id'] ?>/withdraw" style="display:inline;">
                <?= csrf_field() ?>
                <button type="submit" class="btn-outline" style="padding:4px 10px; font-size:0.78rem;">Withdraw</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
