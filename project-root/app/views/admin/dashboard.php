<h1>Dashboard</h1>

<div class="stat-row">
    <div class="stat"><span class="stat-value"><i class="ti ti-building-bank" style="font-size:0.85em; color:var(--ink-muted);" aria-hidden="true"></i> R<?= number_format($stats['aum'], 0) ?></span><span class="stat-label">Total AUM</span></div>
    <div class="stat"><span class="stat-value"><i class="ti ti-building" style="font-size:0.85em; color:var(--ink-muted);" aria-hidden="true"></i> <?= number_format($stats['companyCount']) ?></span><span class="stat-label">Active companies</span></div>
    <div class="stat"><span class="stat-value"><i class="ti ti-users" style="font-size:0.85em; color:var(--ink-muted);" aria-hidden="true"></i> <?= number_format($stats['shareholders']) ?></span><span class="stat-label">Shareholders</span></div>
    <div class="stat"><span class="stat-value"><i class="ti ti-clock" style="font-size:0.85em; color:var(--ink-muted);" aria-hidden="true"></i> <?= number_format($stats['pendingCount']) ?></span><span class="stat-label">Pending commitments</span></div>
    <div class="stat"><span class="stat-value"><i class="ti ti-hourglass" style="font-size:0.85em; color:var(--ink-muted);" aria-hidden="true"></i> R<?= number_format($stats['pendingValue'], 0) ?></span><span class="stat-label">Pending value</span></div>
    <div class="stat"><span class="stat-value"><i class="ti ti-shield-check" style="font-size:0.85em; color:var(--ink-muted);" aria-hidden="true"></i> R<?= number_format($stats['totalArfBalance'], 0) ?></span><span class="stat-label">Total ARF reserves</span></div>
</div>

<h2>Quick actions</h2>
<div class="card-grid">
    <a href="/admin/companies" class="card admin-tile"><i class="ti ti-building" aria-hidden="true"></i> View companies</a>
    <a href="/admin/companies/create" class="card admin-tile"><i class="ti ti-plus" aria-hidden="true"></i> Create a new SPV</a>
    <a href="/admin/settlement" class="card admin-tile"><i class="ti ti-cash" aria-hidden="true"></i> Settlement queue</a>
    <a href="/admin/kyc" class="card admin-tile"><i class="ti ti-shield-check" aria-hidden="true"></i> KYC review</a>
    <a href="/admin/email-queue" class="card admin-tile"><i class="ti ti-mail" aria-hidden="true"></i> Email queue</a>
    <a href="/admin/registry" class="card admin-tile"><i class="ti ti-list-details" aria-hidden="true"></i> Shareholder registry</a>
    <a href="/admin/investors" class="card admin-tile"><i class="ti ti-users" aria-hidden="true"></i> Investors</a>
</div>
