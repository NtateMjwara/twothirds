<?php
/**
 * Create an SPV.
 *
 * One form, four panels. The steps are a presentation layer over a single POST
 * rather than four saved stages: a half-created SPV in the database is worse
 * than a long form, and there is nothing here worth persisting until the whole
 * thing is valid.
 *
 * Without JavaScript every panel is visible and the form submits exactly as it
 * always did. The wizard is an enhancement, not the mechanism.
 *
 * NAV per share is derived, never typed - see the note in step 4.
 */
$old = $old ?? [];
$errorStep = (int) ($errorStep ?? 1);

$v = static fn (string $key, string $default = ''): string => e((string) ($old[$key] ?? $default));
$selected = static fn (string $key, $value): string => (string) ($old[$key] ?? '') === (string) $value ? ' selected' : '';

$steps = [
    1 => ['label' => 'Company',  'icon' => 'ti-building-bank', 'hint' => 'The legal entity'],
    2 => ['label' => 'Asset',    'icon' => 'ti-car',           'hint' => 'What it owns'],
    3 => ['label' => 'Operation','icon' => 'ti-briefcase',     'hint' => 'How it earns'],
    4 => ['label' => 'Shares',   'icon' => 'ti-chart-pie',     'hint' => 'Price and review'],
];
?>
<p class="admin-breadcrumb">
    <a href="/admin/companies"><i class="ti ti-chevron-left" aria-hidden="true"></i> All companies</a>
</p>

<div class="page-title-row">
    <h1>Create a new SPV</h1>
</div>

<?php if (!empty($error)): ?>
    <p class="form-error"><i class="ti ti-alert-circle" aria-hidden="true"></i> <?= e($error) ?></p>
<?php endif; ?>

<ol class="wizard-steps" id="wizardSteps" hidden aria-label="Progress">
    <?php foreach ($steps as $number => $step): ?>
        <li class="wizard-step" data-step-indicator="<?= $number ?>">
            <button type="button" class="wizard-step-btn" data-step-goto="<?= $number ?>">
                <span class="wizard-step-mark"><i class="ti <?= e($step['icon']) ?>" aria-hidden="true"></i></span>
                <span class="wizard-step-text">
                    <span class="wizard-step-label"><?= e($step['label']) ?></span>
                    <span class="wizard-step-hint"><?= e($step['hint']) ?></span>
                </span>
            </button>
        </li>
    <?php endforeach; ?>
</ol>

<form method="post" action="/admin/companies" class="wizard-form" id="spvForm" data-start-step="<?= $errorStep ?>" novalidate>
    <?= csrf_field() ?>

    <!-- ============================================================
         1. Company
         ============================================================ -->
    <fieldset class="wizard-panel" data-step="1">
        <legend class="wizard-legend">
            <span class="wizard-legend-title">The company</span>
            <span class="wizard-legend-sub">
                One company owns one asset. These are its registered details, and they appear
                on the public page exactly as entered.
            </span>
        </legend>

        <div class="field-grid">
            <div class="field field-wide">
                <label for="f-name">Company name <span class="req">required</span></label>
                <input type="text" id="f-name" name="name" value="<?= $v('name') ?>" required
                       autocomplete="off" placeholder="Vukani Mobility SPV 1">
                <p class="field-help">The name on the CIPC registration, not a trading name.</p>
            </div>

            <div class="field">
                <label for="f-reg">Registration number</label>
                <input type="text" id="f-reg" name="registration_number" value="<?= $v('registration_number') ?>"
                       placeholder="2024/123456/07">
                <p class="field-help">Must be unique. Leave blank if incorporation is still in progress.</p>
            </div>

            <div class="field">
                <label for="f-inc">Incorporation date</label>
                <input type="date" id="f-inc" name="incorporation_date" value="<?= $v('incorporation_date') ?>" max="<?= date('Y-m-d') ?>">
            </div>

            <div class="field field-wide">
                <label for="f-addr">Registered address</label>
                <input type="text" id="f-addr" name="registered_address" value="<?= $v('registered_address') ?>">
            </div>
        </div>
    </fieldset>

    <!-- ============================================================
         2. Asset
         ============================================================ -->
    <fieldset class="wizard-panel" data-step="2">
        <legend class="wizard-legend">
            <span class="wizard-legend-title">The asset</span>
            <span class="wizard-legend-sub">
                The specific unit this company owns. Its valuation is what sets the share price
                in step 4, so get that figure right before anything else.
            </span>
        </legend>

        <div class="field-grid">
            <div class="field field-wide">
                <label for="f-class">Asset class <span class="req">required</span></label>
                <select id="f-class" name="asset_class_id" required>
                    <option value="">Select a class&hellip;</option>
                    <?php foreach ($assetClasses as $family => $classes): ?>
                        <optgroup label="<?= e($family) ?>">
                            <?php foreach ($classes as $ac): ?>
                                <option value="<?= (int) $ac['id'] ?>"<?= $selected('asset_class_id', $ac['id']) ?>><?= e($ac['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <p class="field-help">
                    Drives the asset-class filter on discovery. Make and model stay free text below.
                </p>
            </div>

            <div class="field">
                <label for="f-make">Make <span class="req">required</span></label>
                <input type="text" id="f-make" name="make" value="<?= $v('make') ?>" required placeholder="Toyota">
            </div>

            <div class="field">
                <label for="f-model">Model <span class="req">required</span></label>
                <input type="text" id="f-model" name="model" value="<?= $v('model') ?>" required placeholder="Quantum 2.8 GL">
            </div>

            <div class="field">
                <label for="f-year">Year</label>
                <input type="number" id="f-year" name="year" min="1950" max="<?= (int) date('Y') + 1 ?>" value="<?= $v('year') ?>">
            </div>

            <div class="field">
                <label for="f-mileage">Mileage (km)</label>
                <input type="number" id="f-mileage" name="mileage" min="0" value="<?= $v('mileage') ?>">
            </div>

            <div class="field">
                <label for="f-vin">VIN</label>
                <input type="text" id="f-vin" name="vin" value="<?= $v('vin') ?>" maxlength="50"
                       placeholder="Leave blank if not yet delivered">
                <p class="field-help">
                    Optional, but unique when given &mdash; it's how a physical vehicle is tied
                    to exactly one company. Add it once the unit is delivered.
                </p>
            </div>

            <div class="field">
                <label for="f-assetreg">Registration number</label>
                <input type="text" id="f-assetreg" name="asset_registration_number" value="<?= $v('asset_registration_number') ?>" placeholder="CA 123-456">
            </div>

            <div class="field">
                <label for="f-purchase">Purchase price</label>
                <div class="input-prefixed">
                    <span class="input-prefix">R</span>
                    <input type="number" id="f-purchase" name="purchase_price" step="0.01" min="0"
                           value="<?= $v('purchase_price') ?>" data-nav-fallback>
                </div>
            </div>

            <div class="field">
                <label for="f-purchase-date">Purchase date</label>
                <input type="date" id="f-purchase-date" name="purchase_date" value="<?= $v('purchase_date') ?>" max="<?= date('Y-m-d') ?>">
            </div>

            <div class="field">
                <label for="f-valuation">Current valuation</label>
                <div class="input-prefixed">
                    <span class="input-prefix">R</span>
                    <input type="number" id="f-valuation" name="current_valuation" step="0.01" min="0"
                           value="<?= $v('current_valuation') ?>" data-nav-valuation>
                </div>
                <p class="field-help">
                    Sets NAV per share. Leave blank and the purchase price is used instead.
                </p>
            </div>

            <div class="field">
                <label for="f-valuation-date">Valuation date</label>
                <input type="date" id="f-valuation-date" name="valuation_date" value="<?= $v('valuation_date') ?>" max="<?= date('Y-m-d') ?>">
            </div>
        </div>
    </fieldset>

    <!-- ============================================================
         3. Commercial activity
         ============================================================ -->
    <fieldset class="wizard-panel" data-step="3">
        <legend class="wizard-legend">
            <span class="wizard-legend-title">How it earns</span>
            <span class="wizard-legend-sub">
                The commercial activity decides the industry, so an SPV is only ever in one
                sector and the two can't disagree.
            </span>
        </legend>

        <div class="field-grid">
            <div class="field field-wide">
                <label for="f-activity">Commercial activity <span class="req">required</span></label>
                <select id="f-activity" name="activity_type_id" required>
                    <option value="">Select an activity&hellip;</option>
                    <?php foreach ($activityTypes as $sectorName => $types): ?>
                        <optgroup label="<?= e($sectorName) ?>">
                            <?php foreach ($types as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"<?= $selected('activity_type_id', $t['id']) ?>><?= e($t['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="f-operator">Operator</label>
                <input type="text" id="f-operator" name="operator" value="<?= $v('operator') ?>" placeholder="Trading name of the operator">
            </div>

            <div class="field">
                <label for="f-location">Operating area</label>
                <input type="text" id="f-location" name="location" value="<?= $v('location') ?>" placeholder="Soweto, Gauteng">
            </div>

            <div class="field">
                <label for="f-util">Utilisation rate</label>
                <div class="input-suffixed">
                    <input type="number" id="f-util" name="utilisation_rate" step="0.01" min="0" max="100" value="<?= $v('utilisation_rate') ?>">
                    <span class="input-suffix">%</span>
                </div>
                <p class="field-help">
                    Leave blank if it hasn't been measured. Blank and zero mean different things.
                </p>
            </div>

            <div class="field">
                <label for="f-start">Activity start date</label>
                <input type="date" id="f-start" name="activity_start_date" value="<?= $v('activity_start_date', date('Y-m-d')) ?>">
            </div>
        </div>
    </fieldset>

    <!-- ============================================================
         4. Shares and NAV
         ============================================================ -->
    <fieldset class="wizard-panel" data-step="4">
        <legend class="wizard-legend">
            <span class="wizard-legend-title">Shares and price</span>
            <span class="wizard-legend-sub">
                Decide how finely the asset is divided. The price per share follows from that.
            </span>
        </legend>

        <div class="field-grid">
            <div class="field">
                <label for="f-shares">Shares issued <span class="req">required</span></label>
                <input type="number" id="f-shares" name="shares_issued" min="1" step="1"
                       value="<?= $v('shares_issued') ?>" required data-nav-shares>
                <p class="field-help">
                    More shares means a smaller minimum ticket and a lower price each.
                </p>
            </div>

            <div class="field">
                <label for="f-nav">NAV per share <span class="derived">calculated</span></label>
                <div class="input-prefixed is-readonly">
                    <span class="input-prefix">R</span>
                    <input type="text" id="f-nav" value="&mdash;" readonly tabindex="-1" data-nav-output aria-describedby="nav-explainer">
                </div>
                <p class="field-help" id="nav-explainer" data-nav-explainer>
                    Enter a valuation and a share count and this fills in.
                </p>
            </div>
        </div>

        <div class="nav-callout">
            <i class="ti ti-calculator" aria-hidden="true"></i>
            <div>
                <p class="nav-callout-title">Why this isn't typed</p>
                <p class="muted">
                    NAV per share is the asset's value divided by the shares issued. It is the
                    number every percentage on the platform is built on &mdash; the funding
                    target, the progress bar, the trailing yield &mdash; so a typo here would
                    quietly misprice the whole offering. The server recalculates it on save and
                    ignores whatever the browser sends.
                </p>
            </div>
        </div>

        <div class="review-summary" data-review hidden>
            <h3 class="review-summary-title">Before you create it</h3>
            <dl class="review-summary-grid" data-review-grid></dl>
            <p class="muted review-summary-note">
                You can edit everything afterwards except the reference, which is generated
                on save. Photographs, documents and directors are added from the company's
                own screens.
            </p>
        </div>
    </fieldset>

    <div class="wizard-nav">
        <button type="button" class="btn-outline" data-wizard-back hidden>
            <i class="ti ti-chevron-left" aria-hidden="true"></i> Back
        </button>
        <span class="wizard-position muted" data-wizard-position></span>
        <button type="button" class="btn" data-wizard-next hidden>
            Continue <i class="ti ti-chevron-right" aria-hidden="true"></i>
        </button>
        <button type="submit" class="btn" data-wizard-submit>
            <i class="ti ti-check" aria-hidden="true"></i> Create SPV
        </button>
    </div>
</form>

<script src="/assets/js/admin-form-steps.js" defer></script>
