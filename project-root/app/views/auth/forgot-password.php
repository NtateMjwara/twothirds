<div class="narrow-card">
    <h1>Reset your password</h1>
    <?php if (!empty($sent)): ?>
        <p class="form-success">If that email belongs to an account, a reset link has been sent.</p>
        <p class="muted"><a href="/login">Back to login</a></p>
    <?php else: ?>
        <form method="post" action="/forgot-password">
            <?= csrf_field() ?>
            <label>Email<input type="email" name="email" required></label>
            <button type="submit" class="btn">Send reset link</button>
        </form>
        <p class="muted"><a href="/login">Back to login</a></p>
    <?php endif; ?>
</div>
