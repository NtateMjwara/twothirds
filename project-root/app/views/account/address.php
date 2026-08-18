<?php
$currentTab = 'address';
require __DIR__ . '/../partials/_account-header.php';
use app\services\ProfileOptions;

$type = ($_GET['type'] ?? '') === 'postal' ? 'postal' : 'residential';
$address = $type === 'postal' ? ($postal ?? null) : ($residential ?? null);
$val = static fn (string $key): string => e((string) ($address[$key] ?? ''));
$sel = static fn (string $key, string $value): string => (string) ($address[$key] ?? '') === $value ? ' selected' : '';
?>
<?php if (!empty($error)): ?><p class="form-error"><i class="ti ti-alert-circle" aria-hidden="true"></i> <?= e($error) ?></p><?php endif; ?>

<div class="account-panel">
    <h2 class="panel-title">Address</h2>
    <p class="panel-sub muted">
        Your residential address is what proof-of-address documents are checked against, so it
        needs to be where you actually live rather than a postal box.
    </p>

    <!-- Two addresses, one form. Switching is a link so each is a bookmarkable
         URL and neither can be edited by accident. -->
    <div class="segmented address-switch" role="group" aria-label="Address type">
        <a class="segment<?= $type === 'residential' ? ' is-active' : '' ?>" href="/account/address?type=residential">
            Residential<?= !empty($residential) ? ' ✓' : '' ?>
        </a>
        <a class="segment<?= $type === 'postal' ? ' is-active' : '' ?>" href="/account/address?type=postal">
            Postal<?= !empty($postal) ? ' ✓' : '' ?>
        </a>
    </div>

    <form method="post" action="/account/address">
        <?= csrf_field() ?>
        <input type="hidden" name="address_type" value="<?= e($type) ?>">

        <div class="field-grid">
            <div class="field field-wide">
                <label for="a-line1">Street address <span class="req">required</span></label>
                <input type="text" id="a-line1" name="address_line1" value="<?= $val('address_line1') ?>" required
                       placeholder="12 Long Street">
            </div>

            <div class="field field-wide">
                <label for="a-line2">Complex, unit or building</label>
                <input type="text" id="a-line2" name="address_line2" value="<?= $val('address_line2') ?>"
                       placeholder="Unit 4, Riverside Mews">
            </div>

            <div class="field">
                <label for="a-suburb">Suburb</label>
                <input type="text" id="a-suburb" name="suburb" value="<?= $val('suburb') ?>">
            </div>

            <div class="field">
                <label for="a-city">City or town</label>
                <input type="text" id="a-city" name="city" value="<?= $val('city') ?>">
            </div>

            <div class="field">
                <label for="a-province">Province</label>
                <select id="a-province" name="province">
                    <option value="">Select&hellip;</option>
                    <?php foreach (ProfileOptions::provinces() as $province): ?>
                        <option value="<?= e($province) ?>"<?= $sel('province', $province) ?>><?= e($province) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="a-postal">Postal code</label>
                <input type="text" id="a-postal" name="postal_code" value="<?= $val('postal_code') ?>"
                       inputmode="numeric" maxlength="10">
            </div>

            <div class="field">
                <label for="a-country">Country</label>
                <select id="a-country" name="country">
                    <?php foreach (ProfileOptions::countries() as $country): ?>
                        <option value="<?= e($country) ?>"<?= (($address['country'] ?? 'South Africa') === $country) ? ' selected' : '' ?>>
                            <?= e($country) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if ($type === 'residential'): ?>
            <label class="checkbox-row">
                <input type="checkbox" name="postal_same" value="1">
                <span>My postal address is the same as this one</span>
            </label>
        <?php endif; ?>

        <div class="panel-actions">
            <button type="submit" class="btn"><i class="ti ti-device-floppy" aria-hidden="true"></i> Save address</button>
        </div>
    </form>
</div>
