<?php require __DIR__ . '/../partials/account-nav.php'; ?>
<h1>Address</h1>
<?php if (!empty($saved)): ?><p class="form-success">Saved.</p><?php endif; ?>

<?php
$residential = null;
$postal = null;
foreach ($addresses as $a) {
    if ($a['address_type'] === 'residential') $residential = $a;
    if ($a['address_type'] === 'postal') $postal = $a;
}
?>

<h2>Residential</h2>
<div class="settings-card">
    <form method="post" action="/account/address">
        <?= csrf_field() ?>
        <input type="hidden" name="address_type" value="residential">
        <label>Address line 1<input type="text" name="address_line1" value="<?= e($residential['address_line1'] ?? '') ?>" required></label>
        <label>Address line 2<input type="text" name="address_line2" value="<?= e($residential['address_line2'] ?? '') ?>"></label>
        <label>City<input type="text" name="city" value="<?= e($residential['city'] ?? '') ?>"></label>
        <label>Province<input type="text" name="province" value="<?= e($residential['province'] ?? '') ?>"></label>
        <label>Postal code<input type="text" name="postal_code" value="<?= e($residential['postal_code'] ?? '') ?>"></label>
        <label>Country<input type="text" name="country" value="<?= e($residential['country'] ?? 'South Africa') ?>"></label>
        <button type="submit" class="btn">Save residential address</button>
    </form>
</div>

<h2>Postal</h2>
<div class="settings-card">
    <form method="post" action="/account/address">
        <?= csrf_field() ?>
        <input type="hidden" name="address_type" value="postal">
        <label>Address line 1<input type="text" name="address_line1" value="<?= e($postal['address_line1'] ?? '') ?>"></label>
        <label>Address line 2<input type="text" name="address_line2" value="<?= e($postal['address_line2'] ?? '') ?>"></label>
        <label>City<input type="text" name="city" value="<?= e($postal['city'] ?? '') ?>"></label>
        <label>Province<input type="text" name="province" value="<?= e($postal['province'] ?? '') ?>"></label>
        <label>Postal code<input type="text" name="postal_code" value="<?= e($postal['postal_code'] ?? '') ?>"></label>
        <label>Country<input type="text" name="country" value="<?= e($postal['country'] ?? 'South Africa') ?>"></label>
        <button type="submit" class="btn">Save postal address</button>
    </form>
</div>
