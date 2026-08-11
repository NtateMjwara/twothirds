<div class="narrow-card">
    <h1>Resend verification email</h1>
    <?php if (!empty($sent)): ?>
        <p class="form-success">If that email needs verification, we've sent a new link.</p>
        <p class="muted"><a href="/login">Back to login</a></p>
    <?php else: ?>
        <form method="post" action="/resend-verification">
            <?= csrf_field() ?>
            <label>Email<input type="email" name="email" required></label>
            <button type="submit" class="btn">Send link</button>
        </form>
        <p class="muted"><a href="/login">Back to login</a></p>
    <?php endif; ?>
</div>
