<?php require __DIR__ . '/../partials/account-nav.php'; ?>
<h1>Notifications</h1>
<?php if (empty($notifications)): ?>
    <p>No notifications yet.</p>
<?php else: ?>
    <form method="post" action="/account/notifications/mark-all-read" style="margin-bottom:1rem;">
        <?= csrf_field() ?>
        <button type="submit">Mark all as read</button>
    </form>
    <ul style="list-style:none; padding:0; margin:0;">
    <?php foreach ($notifications as $n): ?>
        <li style="padding:10px 0; border-bottom:1px solid #eee; <?= $n['read_at'] ? '' : 'font-weight:600;' ?>">
            <?= e($n['payload']) ?>
            <span class="muted" style="font-weight:400;">(<?= e($n['created_at']) ?>)</span>
            <?php if (!$n['read_at']): ?>
                <form method="post" action="/account/notifications/<?= (int) $n['id'] ?>/read" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-button">Mark read</button>
                </form>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
