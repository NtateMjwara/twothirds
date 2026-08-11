<div class="narrow-card">
    <h1>Log in</h1>
    <?php if (!empty($error)): ?>
        <p class="form-error">
            <?= e($error) ?>
            <?php if (!empty($unverifiedEmail)): ?>
                <br><a href="/resend-verification">Resend verification email</a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
    <form method="post" action="/login">
        <?= csrf_field() ?>
        <label>Email<input type="email" name="email" value="<?= e($unverifiedEmail ?? '') ?>" required></label>
        <label>Password<input type="password" name="password" required></label>
        <button type="submit" class="btn">Log in</button>
    </form>
    <p class="muted">New here? <a href="/register">Create an account</a></p>
    <p class="muted"><a href="/forgot-password">Forgot your password?</a></p>
</div>
