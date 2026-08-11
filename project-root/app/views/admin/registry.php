<h1>Shareholder registry</h1>
<form method="get" action="/admin/registry" class="search-bar">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search investor or company">
    <button type="submit" class="btn">Search</button>
</form>

<?php if (empty($rows)): ?>
    <p class="muted">No matching holdings.</p>
<?php else: ?>
<table>
    <tr><th>Investor</th><th>Company</th><th>Shares</th><th>Value</th><th>Settled</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td>
            <a href="/admin/investors/<?= (int) $r['user_id'] ?>"><?= e(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))) ?></a>
            <span class="muted">(<?= e($r['email']) ?>)</span>
        </td>
        <td>
            <a href="/company/<?= e($r['company_reference']) ?>"><?= e($r['company_name']) ?></a>
            <span class="ref-badge"><?= e($r['company_reference']) ?></span>
        </td>
        <td><?= number_format((int) $r['shares']) ?></td>
        <td>R<?= number_format($r['shares'] * $r['nav_per_share'], 2) ?></td>
        <td><?= e($r['settled_at']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
