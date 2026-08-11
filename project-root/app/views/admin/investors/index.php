<h1>Investors</h1>
<form method="get" action="/admin/investors" class="search-bar">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search name or email">
    <button type="submit" class="btn">Search</button>
</form>

<?php if (empty($investors)): ?>
    <p class="muted">No matching investors.</p>
<?php else: ?>
<table>
    <tr><th>Name</th><th>Email</th><th>Email verified</th><th>KYC</th><th>Status</th><th></th></tr>
    <?php foreach ($investors as $inv): ?>
    <tr>
        <td><?= e(trim(($inv['first_name'] ?? '') . ' ' . ($inv['last_name'] ?? ''))) ?: '&mdash;' ?></td>
        <td><?= e($inv['email']) ?></td>
        <td><?= $inv['email_verified_at'] ? 'Yes' : 'No' ?></td>
        <td>
            <?php if ($inv['kyc_status']): ?>
                <span class="status-badge status-<?= e($inv['kyc_status']) ?>"><?= e($inv['kyc_status']) ?></span>
            <?php else: ?>
                <span class="muted">not submitted</span>
            <?php endif; ?>
        </td>
        <td><span class="status-badge status-<?= e($inv['status']) ?>"><?= e($inv['status']) ?></span></td>
        <td><a href="/admin/investors/<?= (int) $inv['id'] ?>">View</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
