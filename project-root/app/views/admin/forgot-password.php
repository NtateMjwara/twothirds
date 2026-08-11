<h1>Reset your password</h1>
<?php if (!empty($sent)): ?>
    <p>If that email belongs to an admin account, a reset link has been sent.</p>
    <p><a href="/admin/login">Back to login</a></p>
<?php else: ?>
    <form method="post" action="/admin/forgot-password">
        <?= csrf_field() ?>
        <label>Email<br><input type="email" name="email" required></label><br><br>
        <button type="submit">Send reset link</button>
    </form>
<?php endif; ?>
