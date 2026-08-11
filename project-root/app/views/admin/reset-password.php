<h1>Set a new password</h1>
<?php if (!empty($invalid)): ?>
    <p>This reset link is invalid or has expired. <a href="/admin/forgot-password">Request a new one</a>.</p>
<?php else: ?>
    <?php if (!empty($error)): ?><p style="color:#c00;"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="/admin/reset-password/<?= e($token) ?>">
        <?= csrf_field() ?>
        <label>New password<br><input type="password" name="password" required minlength="10"></label><br><br>
        <button type="submit">Set password</button>
    </form>
<?php endif; ?>
