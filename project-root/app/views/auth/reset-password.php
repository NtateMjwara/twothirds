<div class="narrow-card">
    <h1>Set a new password</h1>
    <?php if (!empty($invalid)): ?>
        <p class="muted">This reset link is invalid or has expired. <a href="/forgot-password">Request a new one</a>.</p>
    <?php else: ?>
        <?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
        <form method="post" action="/reset-password/<?= e($token) ?>">
            <?= csrf_field() ?>
            <label>New password<input type="password" name="password" required minlength="10"></label>
            <button type="submit" class="btn">Set password</button>
        </form>
    <?php endif; ?>
</div>
