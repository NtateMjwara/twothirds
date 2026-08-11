<p><a href="/discover">&larr; Back to companies</a></p>

<?php $primaryActivity = $activities[0] ?? null; ?>
<p class="muted"><?= e($primaryActivity['location'] ?? '') ?><?= $primaryActivity ? ' &middot; ' . e($primaryActivity['activity_type']) : '' ?></p>

<div class="page-title-row">
    <h1><?= e($company['name']) ?></h1>
    <span class="ref-badge"><?= e($company['reference']) ?></span>
</div>

<div class="stat-row">
    <div class="stat">
        <span class="stat-value"><i class="ti ti-report-money" style="font-size:0.85em; color:var(--ink-muted);" aria-hidden="true"></i> R<?= number_format((float) ($asset['current_valuation'] ?? 0), 0) ?></span>
        <span class="stat-label">Valuation</span>
    </div>
    <div class="stat">
        <span class="stat-value"><i class="ti ti-coin" style="font-size:0.85em; color:var(--ink-muted);" aria-hidden="true"></i> R<?= number_format((float) $company['nav_per_share'], 2) ?></span>
        <span class="stat-label">NAV/share</span>
    </div>
    <div class="stat">
        <span class="stat-value"><i class="ti ti-chart-pie" style="font-size:0.85em; color:var(--ink-muted);" aria-hidden="true"></i> <?= number_format($sharesAvailable) ?></span>
        <span class="stat-label">Available</span>
    </div>
</div>

<p>
    <a href="/commit/<?= e($company['reference']) ?>" class="btn"><i class="ti ti-circle-plus" aria-hidden="true"></i> Commit to invest</a>
    <?php if (!empty($_SESSION['user_id'])): ?>
        <form method="post" action="/watchlist/<?= e($company['reference']) ?>/toggle" style="display:inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn-outline">
                <i class="ti ti-<?= $isWatching ? 'bookmark-off' : 'bookmark' ?>" aria-hidden="true"></i>
                <?= $isWatching ? 'Remove from watchlist' : 'Save to watchlist' ?>
            </button>
        </form>
    <?php else: ?>
        <a href="/login">Log in to save</a>
    <?php endif; ?>
</p>

<div class="tabs">
    <button class="tab-btn active" data-tab="overview"><i class="ti ti-list-details" aria-hidden="true"></i> Overview</button>
    <button class="tab-btn" data-tab="asset"><i class="ti ti-car" aria-hidden="true"></i> Asset</button>
    <button class="tab-btn" data-tab="financials"><i class="ti ti-report-money" aria-hidden="true"></i> Financials</button>
    <button class="tab-btn" data-tab="governance"><i class="ti ti-users" aria-hidden="true"></i> Governance</button>
    <button class="tab-btn" data-tab="documents"><i class="ti ti-file-text" aria-hidden="true"></i> Documents</button>
</div>

<div id="tab-overview" class="tab-panel">
    <p><?= (int) $company['shares_issued'] ?> shares issued</p>
    <p>Asset: <?= e(trim(($asset['make'] ?? '') . ' ' . ($asset['model'] ?? ''))) ?: 'Not recorded yet' ?></p>
    <?php foreach ($activities as $a): ?>
        <p><?= e($a['activity_type']) ?> &mdash; <?= e($a['location']) ?>
            (since <?= e($a['start_date']) ?><?= $a['end_date'] ? ', to ' . e($a['end_date']) : '' ?>)</p>
    <?php endforeach; ?>
</div>

<div id="tab-asset" class="tab-panel" style="display:none;">
    <?php if ($asset): ?>
    <table>
        <tr><td>VIN</td><td><?= e($asset['vin']) ?></td></tr>
        <tr><td>Purchase price</td><td>R<?= number_format((float) $asset['purchase_price'], 0) ?></td></tr>
        <tr><td>Valuation date</td><td><?= e($asset['valuation_date']) ?></td></tr>
        <tr><td>Insurance</td><td><?= e($asset['insurance_status']) ?></td></tr>
        <tr><td>Roadworthy</td><td><?= e($asset['roadworthy_status']) ?></td></tr>
        <tr><td>Mileage</td><td><?= number_format((int) $asset['mileage']) ?> km</td></tr>
    </table>
    <?php else: ?>
        <p class="muted"><i class="ti ti-car-off" aria-hidden="true"></i> No asset on record yet.</p>
    <?php endif; ?>
</div>

<div id="tab-financials" class="tab-panel" style="display:none;">
    <?php if ($latestPeriod): ?>
    <table>
        <tr><th></th><th>Latest month</th><th>TTM</th></tr>
        <tr>
            <td>Gross revenue</td>
            <td>R<?= number_format((float) $latestPeriod['gross_revenue'], 0) ?></td>
            <td>R<?= number_format((float) $ttm['revenue'], 0) ?></td>
        </tr>
        <tr>
            <td>Net operating income</td>
            <td>R<?= number_format((float) $latestPeriod['net_operating_income'], 0) ?></td>
            <td>R<?= number_format((float) $ttm['profit'], 0) ?></td>
        </tr>
        <tr>
            <td>Asset Replacement Fund</td>
            <td colspan="2">R<?= number_format((float) $company['arf_balance'], 0) ?> of R<?= number_format((float) $company['arf_target'], 0) ?></td>
        </tr>
    </table>
    <?php else: ?>
        <p class="muted"><i class="ti ti-report-money" aria-hidden="true"></i> No financial periods recorded yet.</p>
    <?php endif; ?>
</div>

<div id="tab-governance" class="tab-panel" style="display:none;">
    <p>Corporate secretary: <?= e($company['corporate_secretary']) ?></p>
    <p><?= e($company['registration_number']) ?> &middot; incorporated <?= e($company['incorporation_date']) ?></p>
    <?php if ($directors): ?>
        <h3>Board</h3>
        <ul>
        <?php foreach ($directors as $d): ?>
            <li><i class="ti ti-user" style="font-size:0.85em; color:var(--ink-muted);" aria-hidden="true"></i> <?= e($d['full_name']) ?> &mdash; <?= e($d['role']) ?></li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="muted"><i class="ti ti-users" aria-hidden="true"></i> No directors on record yet.</p>
    <?php endif; ?>
</div>

<div id="tab-documents" class="tab-panel" style="display:none;">
    <?php if ($documents): ?>
        <ul style="list-style:none; padding:0;">
        <?php foreach ($documents as $d): ?>
            <li style="padding:6px 0;">
                <i class="ti ti-file-text" style="font-size:0.9em; color:var(--ink-muted);" aria-hidden="true"></i>
                <a href="/documents/<?= (int) $d['id'] ?>"><?= e($d['doc_type']) ?></a><?= $d['verified'] ? ' (verified)' : '' ?>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="muted"><i class="ti ti-file-off" aria-hidden="true"></i> No documents uploaded yet.</p>
    <?php endif; ?>
</div>

<script src="/assets/js/company-tabs.js"></script>
