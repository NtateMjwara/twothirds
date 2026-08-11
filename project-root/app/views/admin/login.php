<h1>Admin login</h1>
<?php if (!empty($error)): ?><p style="color:#c00;"><?= e($error) ?></p><?php endif; ?>
<form method="post" action="/admin/login">
    <?= csrf_field() ?>
    <label>Email<br><input type="email" name="email" required></label><br><br>
    <label>Password<br><input type="password" name="password" required></label><br><br>
    <button type="submit">Log in</button>
</form>
<p><a href="/admin/forgot-password">Forgot password?</a></p>
