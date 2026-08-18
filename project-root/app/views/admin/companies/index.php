<?php
/**
 * Companies list.
 *
 * The per-company admin screens - edit, photos, updates, financials, documents,
 * directors - had no hub. Each was reachable only by typing its URL or from a
 * link buried on another page. They're grouped here.
 */
?>
<div class="page-title-row">
    <h1>Companies</h1>
    <?php if ($canManage): ?>
        <a href="/admin/companies/create" class="btn"><i class="ti ti-plus" aria-hidden="true"></i> Create a new SPV</a>
    <?php endif; ?>
</div>

<?php if (empty($companies)): ?>
    <div class="empty-state">
        <div class="asset-icon"><i class="ti ti-building-off" aria-hidden="true"></i></div>
        <h3>No companies yet</h3>
        <p class="muted">Create the first SPV to get a listing onto the platform.</p>
    </div>
<?php else: ?>
<div class="table-scroll">
    <table class="admin-table">
        <thead>
            <tr>
                <th scope="col">Reference</th>
                <th scope="col">Name</th>
                <th scope="col">Status</th>
                <th scope="col">Shares issued</th>
                <th scope="col">NAV/share</th>
                <th scope="col">Manage</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($companies as $c): ?>
            <tr>
                <td><span class="ref-badge"><?= e($c['reference']) ?></span></td>
                <td><a href="/company/<?= e($c['reference']) ?>" target="_blank" rel="noopener"><?= e($c['name']) ?></a></td>
                <td><span class="status-badge status-<?= e($c['status']) ?>"><?= e($c['status']) ?></span></td>
                <td class="is-mono"><?= number_format((int) $c['shares_issued']) ?></td>
                <td class="is-mono">R<?= number_format((float) $c['nav_per_share'], 2) ?></td>
                <td>
                    <?php if ($canManage): ?>
                    <div class="row-actions">
                        <a href="/admin/companies/<?= e($c['reference']) ?>/edit"><i class="ti ti-pencil" aria-hidden="true"></i> Edit</a>
                        <a href="/admin/companies/<?= e($c['reference']) ?>/images"><i class="ti ti-photo" aria-hidden="true"></i> Photos</a>
                        <a href="/admin/companies/<?= e($c['reference']) ?>/updates"><i class="ti ti-timeline" aria-hidden="true"></i> Updates</a>
                        <a href="/admin/companies/<?= e($c['reference']) ?>/financials"><i class="ti ti-report-money" aria-hidden="true"></i> Financials</a>
                        <a href="/admin/companies/<?= e($c['reference']) ?>/documents"><i class="ti ti-file-text" aria-hidden="true"></i> Documents</a>
                        <a href="/admin/companies/<?= e($c['reference']) ?>/directors"><i class="ti ti-users" aria-hidden="true"></i> Directors</a>
                    </div>
                    <?php else: ?>
                        <span class="muted">View only</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
