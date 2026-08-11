<?php require __DIR__ . '/../partials/account-nav.php'; ?>
<h1>Profile</h1>
<?php if (!empty($saved)): ?><p class="form-success">Saved.</p><?php endif; ?>
<div class="settings-card">
    <form method="post" action="/account/profile">
        <?= csrf_field() ?>
        <label>First name<input type="text" name="first_name" value="<?= e($profile['first_name'] ?? '') ?>" required></label>
        <label>Last name<input type="text" name="last_name" value="<?= e($profile['last_name'] ?? '') ?>" required></label>
        <label>Phone<input type="tel" name="phone" value="<?= e($profile['phone'] ?? '') ?>"></label>
        <label>Date of birth<input type="date" name="date_of_birth" value="<?= e($profile['date_of_birth'] ?? '') ?>"></label>
        <button type="submit" class="btn">Save</button>
    </form>
</div>
