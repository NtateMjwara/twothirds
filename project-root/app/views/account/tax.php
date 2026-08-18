<?php
$currentTab = 'tax';
require __DIR__ . '/../partials/_account-header.php';
use app\services\ProfileOptions;

$isResident = $tax === null ? true : (int) $tax['is_sa_tax_resident'] === 1;
?>
<?php if (!empty($error)): ?><p class="form-error"><i class="ti ti-alert-circle" aria-hidden="true"></i> <?= e($error) ?></p><?php endif; ?>

<div class="account-panel">
    <h2 class="panel-title">Tax information</h2>
    <p class="panel-sub muted">
        Collected so distributions can be reported correctly. We don't give tax advice and
        can't tell you how any of this will be treated in your hands &mdash; that depends on
        your own circumstances.
    </p>

    <form method="post" action="/account/tax">
        <?= csrf_field() ?>

        <label class="checkbox-row checkbox-row-lead">
            <input type="checkbox" name="is_sa_tax_resident" value="1" <?= $isResident ? 'checked' : '' ?>
                   data-tax-resident>
            <span>
                <strong>I am a South African tax resident</strong>
                <span class="muted">Uncheck if you pay tax in another country.</span>
            </span>
        </label>

        <div class="field-grid">
            <div class="field">
                <label for="t-number">SARS tax reference number</label>
                <input type="text" id="t-number" name="tax_number" inputmode="numeric" maxlength="10"
                       placeholder="<?= $taxNumberHint ? 'On file, ending ' . e($taxNumberHint) : '10 digits' ?>">
                <p class="field-help">
                    <?php if ($taxNumberHint): ?>
                        Stored and encrypted. Leave blank to keep it unchanged.
                    <?php else: ?>
                        Ten digits, from your SARS correspondence or IRP5.
                    <?php endif; ?>
                </p>
            </div>

            <div class="field">
                <label for="t-country">Country of tax residence</label>
                <select id="t-country" name="foreign_tax_country" <?= $isResident ? 'disabled' : '' ?>>
                    <option value="">Select&hellip;</option>
                    <?php foreach (ProfileOptions::countries() as $country): ?>
                        <option value="<?= e($country) ?>"<?= ($tax['foreign_tax_country'] ?? '') === $country ? ' selected' : '' ?>>
                            <?= e($country) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="field-help">Only needed if you're not a South African tax resident.</p>
            </div>

            <div class="field">
                <label for="t-foreign">Foreign tax number</label>
                <input type="text" id="t-foreign" name="foreign_tax_number" maxlength="60"
                       placeholder="<?= $foreignNumberHint ? 'On file, ending ' . e($foreignNumberHint) : '' ?>"
                       <?= $isResident ? 'disabled' : '' ?>>
            </div>

            <div class="field">
                <label for="t-reason">If you have no tax number, why not?</label>
                <input type="text" id="t-reason" name="no_tin_reason" maxlength="255"
                       value="<?= e($tax['no_tin_reason'] ?? '') ?>"
                       placeholder="e.g. below the filing threshold">
                <p class="field-help">
                    Some people genuinely aren't registered. The reason is what gets recorded,
                    rather than an empty field.
                </p>
            </div>
        </div>

        <div class="callout callout-plain">
            <i class="ti ti-lock" aria-hidden="true"></i>
            <p>
                Tax numbers are encrypted before they're stored, the same way bank account
                numbers are, and are never shown back to you in full once saved.
            </p>
        </div>

        <div class="panel-actions">
            <button type="submit" class="btn"><i class="ti ti-device-floppy" aria-hidden="true"></i> Save tax details</button>
        </div>
    </form>
</div>
