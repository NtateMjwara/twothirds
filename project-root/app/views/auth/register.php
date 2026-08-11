<div class="narrow-card">
    <h1>Create an account</h1>
    <?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="/register">
        <?= csrf_field() ?>
        <label>First name<input type="text" name="first_name" required></label>
        <label>Last name<input type="text" name="last_name" required></label>
        <label>Phone<input type="tel" name="phone"></label>
        <label>Email<input type="email" name="email" required></label>
        <label>Password<input type="password" name="password" required minlength="10"></label>
        <button type="submit" class="btn">Create account</button>
    </form>
    <p class="muted">Already have an account? <a href="/login">Log in</a></p>
</div>
