<h1>KYC review queue</h1>
<?php if (empty($queue)): ?>
    <p class="muted">No pending KYC submissions.</p>
<?php else: ?>
<table>
    <tr><th>Investor</th><th>ID type</th><th>ID number</th><th>Document</th><th>Submitted</th><th></th></tr>
    <?php foreach ($queue as $k): ?>
    <tr>
        <td><?= e(trim(($k['first_name'] ?? '') . ' ' . ($k['last_name'] ?? ''))) ?> <span class="muted">(<?= e($k['user_email']) ?>)</span></td>
        <td><?= e($k['id_type']) ?></td>
        <td><?= e($k['id_number']) ?></td>
        <td><a href="/documents/<?= (int) $k['document_id'] ?>" target="_blank">View</a></td>
        <td><?= e($k['updated_at']) ?></td>
        <td class="admin-actions">
            <form method="post" action="/admin/kyc/<?= (int) $k['id'] ?>/approve">
                <?= csrf_field() ?>
                <button type="submit" class="btn-compact btn-positive">Approve</button>
            </form>
            <form method="post" action="/admin/kyc/<?= (int) $k['id'] ?>/reject">
                <?= csrf_field() ?>
                <input type="text" name="reason" placeholder="Reason" style="width:120px;">
                <button type="submit" class="btn-compact btn-negative">Reject</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
