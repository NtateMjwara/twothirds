<h1>Settlement queue</h1>
<?php if (empty($queue)): ?>
    <p class="muted">No pending commitments.</p>
<?php else: ?>
<table>
    <tr><th>Reference</th><th>Investor</th><th>Company</th><th>Shares</th><th>Requested</th><th></th></tr>
    <?php foreach ($queue as $q): ?>
    <tr>
        <td><span class="ref-badge"><?= e($q['reference']) ?></span></td>
        <td><?= e(trim(($q['first_name'] ?? '') . ' ' . ($q['last_name'] ?? ''))) ?> <span class="muted">(<?= e($q['user_email']) ?>)</span></td>
        <td><?= e($q['company_name']) ?> <span class="ref-badge"><?= e($q['company_reference']) ?></span></td>
        <td><?= number_format((int) $q['shares_requested']) ?></td>
        <td><?= e($q['created_at']) ?></td>
        <td class="admin-actions">
            <form method="post" action="/admin/settlement/<?= (int) $q['id'] ?>/confirm">
                <?= csrf_field() ?>
                <button type="submit" class="btn-compact btn-positive">Confirm settled</button>
            </form>
            <form method="post" action="/admin/settlement/<?= (int) $q['id'] ?>/cancel">
                <?= csrf_field() ?>
                <button type="submit" class="btn-compact btn-negative">Cancel</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
