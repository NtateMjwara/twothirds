<h1>Companies</h1>
<?php if (!empty($_GET['created'])): ?>
    <p class="form-success"><?= e($_GET['created']) ?> created successfully.</p>
<?php endif; ?>
<p><a href="/admin/companies/create" class="btn">+ Create a new SPV</a></p>
<table>
    <tr><th>Reference</th><th>Name</th><th>Status</th><th>Shares issued</th><th></th></tr>
    <?php foreach ($companies as $c): ?>
    <tr>
        <td><span class="ref-badge"><?= e($c['reference']) ?></span></td>
        <td><?= e($c['name']) ?></td>
        <td><span class="status-badge status-<?= e($c['status']) ?>"><?= e($c['status']) ?></span></td>
        <td><?= number_format((int) $c['shares_issued']) ?></td>
        <td class="admin-actions">
            <a href="/company/<?= e($c['reference']) ?>">View</a>
            <a href="/admin/companies/<?= e($c['reference']) ?>/edit">Edit</a>
            <a href="/admin/companies/<?= e($c['reference']) ?>/financials">Financials</a>
            <a href="/admin/companies/<?= e($c['reference']) ?>/documents">Documents</a>
            <a href="/admin/companies/<?= e($c['reference']) ?>/directors">Directors</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
