<?php
$currentTab = 'profile';
require __DIR__ . '/../partials/_account-header.php';

$old = $old ?? null;
// After a failed submit, show what was typed rather than what is stored.
$val = static function (string $key, string $default = '') use ($profile, $old): string {
    $source = $old ?? $profile ?? [];
    return e((string) ($source[$key] ?? $default));
};
$isSelected = static function (string $key, $value) use ($profile, $old): string {
    $source = $old ?? $profile ?? [];
    return (string) ($source[$key] ?? '') === (string) $value ? ' selected' : '';
};
use app\services\ProfileOptions;
?>
<?php if (!empty($error)): ?><p class="form-error"><i class="ti ti-alert-circle" aria-hidden="true"></i> <?= e($error) ?></p><?php endif; ?>

<div class="account-panel">
    <h2 class="panel-title">Personal details</h2>
    <p class="panel-sub muted">
        These have to match your identity document. If your name here and on your ID differ,
        verification will be rejected.
    </p>

    <form method="post" action="/account/profile">
        <?= csrf_field() ?>

        <div class="field-grid">
            <div class="field">
                <label for="p-title">Title</label>
                <select id="p-title" name="title">
                    <option value="">Select&hellip;</option>
                    <?php foreach (ProfileOptions::titles() as $title): ?>
                        <option value="<?= e($title) ?>"<?= $isSelected('title', $title) ?>><?= e($title) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="p-first">First name <span class="req">required</span></label>
                <input type="text" id="p-first" name="first_name" value="<?= $val('first_name') ?>" required>
            </div>

            <div class="field">
                <label for="p-initials">Initials</label>
                <input type="text" id="p-initials" name="initials" maxlength="10" value="<?= $val('initials') ?>">
            </div>

            <div class="field">
                <label for="p-last">Surname <span class="req">required</span></label>
                <input type="text" id="p-last" name="last_name" value="<?= $val('last_name') ?>" required>
            </div>

            <div class="field">
                <label for="p-preferred">Preferred name</label>
                <input type="text" id="p-preferred" name="preferred_name" value="<?= $val('preferred_name') ?>">
                <p class="field-help">What we'll call you around the site. Your legal name stays on the record.</p>
            </div>

            <div class="field">
                <label for="p-dob">Date of birth</label>
                <input type="date" id="p-dob" name="date_of_birth" value="<?= $val('date_of_birth') ?>"
                       max="<?= date('Y-m-d', strtotime('-18 years')) ?>">
                <p class="field-help">You must be 18 or older to hold shares.</p>
            </div>

            <div class="field">
                <label for="p-gender">Gender</label>
                <select id="p-gender" name="gender">
                    <option value="">Select&hellip;</option>
                    <?php foreach (ProfileOptions::genders() as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $isSelected('gender', $key) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Email address</label>
                <input type="email" value="<?= e($user['email'] ?? '') ?>" readonly tabindex="-1" class="is-readonly">
                <p class="field-help">
                    Your email is your login, so it can't be changed here.
                    <a href="/account/notifications">Contact support</a> if it needs to move.
                </p>
            </div>

            <div class="field">
                <label for="p-code">Mobile country code</label>
                <select id="p-code" name="calling_code">
                    <?php foreach (ProfileOptions::callingCodes() as $code => $label): ?>
                        <option value="<?= e($code) ?>"<?= $isSelected('calling_code', $code) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="p-phone">Mobile number</label>
                <input type="tel" id="p-phone" name="phone" value="<?= $val('phone') ?>" placeholder="79 184 4808">
            </div>

            <div class="field">
                <label for="p-workcode">Work country code</label>
                <select id="p-workcode" name="work_calling_code">
                    <option value="">Select&hellip;</option>
                    <?php foreach (ProfileOptions::callingCodes() as $code => $label): ?>
                        <option value="<?= e($code) ?>"<?= $isSelected('work_calling_code', $code) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="p-work">Work number</label>
                <input type="tel" id="p-work" name="work_phone" value="<?= $val('work_phone') ?>">
            </div>

            <div class="field">
                <label for="p-cob">Country of birth</label>
                <select id="p-cob" name="country_of_birth">
                    <option value="">Select&hellip;</option>
                    <?php foreach (ProfileOptions::countries() as $country): ?>
                        <option value="<?= e($country) ?>"<?= $isSelected('country_of_birth', $country) ?>><?= e($country) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="p-city">City of birth</label>
                <input type="text" id="p-city" name="city_of_birth" value="<?= $val('city_of_birth') ?>">
            </div>

            <div class="field">
                <label for="p-cor">Country of residence</label>
                <select id="p-cor" name="country_of_residence">
                    <?php foreach (ProfileOptions::countries() as $country): ?>
                        <option value="<?= e($country) ?>"<?= $isSelected('country_of_residence', $country) ?>><?= e($country) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="p-coc">Country of citizenship</label>
                <select id="p-coc" name="country_of_citizenship">
                    <?php foreach (ProfileOptions::countries() as $country): ?>
                        <option value="<?= e($country) ?>"<?= $isSelected('country_of_citizenship', $country) ?>><?= e($country) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="p-marital">Marital status</label>
                <select id="p-marital" name="marital_status">
                    <option value="">Select&hellip;</option>
                    <?php foreach (ProfileOptions::maritalStatuses() as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $isSelected('marital_status', $key) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="field-help">
                    Asked because marriage in community of property affects who can bind an estate.
                </p>
            </div>
        </div>

        <div class="panel-actions">
            <button type="submit" class="btn"><i class="ti ti-device-floppy" aria-hidden="true"></i> Save</button>
        </div>
    </form>
</div>
