<?php
/**
 * Edit an SPV.
 *
 * Tabs, not steps. Creating an SPV is a linear task you do once; editing one is
 * almost always "change this one thing" — so every section is reachable
 * immediately rather than sitting behind a Continue button. Same visual language
 * as the create wizard, different navigation model, one submit.
 *
 * NAV per share is derived here exactly as it is on create. See the Shares tab.
 */
$currentTab = 'edit';
require __DIR__ . '/../partials/_company-tabs.php';

$committed = (int) ($committed ?? 0);
$storedNav = (float) $company['nav_per_share'];

// The value NAV is calculated from: current valuation, falling back to the
// purchase price recorded when the SPV was created. Purchase price isn't
// editable here — it's a historic fact, not a position.
$purchasePrice = (float) ($asset['purchase_price'] ?? 0);

$sections = [
    1 => ['label' => 'Company',   'icon' => 'ti-building-bank', 'hint' => 'Registered details'],
    2 => ['label' => 'Listing',   'icon' => 'ti-article',       'hint' => 'Copy and offer window'],
    3 => ['label' => 'Asset',     'icon' => 'ti-car',           'hint' => 'Unit and valuation'],
    4 => ['label' => 'Operation', 'icon' => 'ti-briefcase',     'hint' => 'How it earns'],
    5 => ['label' => 'Shares',    'icon' => 'ti-chart-pie',     'hint' => 'Split and price'],
];
?>

<?php if (!empty($error)): ?>
    <p class="form-error"><i class="ti ti-alert-circle" aria-hidden="true"></i> <?= e($error) ?></p>
<?php endif; ?>

<ol class="wizard-steps" id="wizardSteps" hidden aria-label="Sections">
    <?php foreach ($sections as $number => $section): ?>
        <li class="wizard-step" data-step-indicator="<?= $number ?>">
            <button type="button" class="wizard-step-btn" data-step-goto="<?= $number ?>">
                <span class="wizard-step-mark"><i class="ti <?= e($section['icon']) ?>" aria-hidden="true"></i></span>
                <span class="wizard-step-text">
                    <span class="wizard-step-label"><?= e($section['label']) ?></span>
                    <span class="wizard-step-hint"><?= e($section['hint']) ?></span>
                </span>
            </button>
        </li>
    <?php endforeach; ?>
</ol>

<form method="post" action="/admin/companies/<?= e($company['reference']) ?>/edit"
      class="wizard-form" id="spvForm" data-mode="tabs" data-start-step="<?= (int) ($errorStep ?? 1) ?>"
      data-dirty-warning novalidate>
    <?= csrf_field() ?>

    <!-- ============================================================
         1. Company
         ============================================================ -->
    <fieldset class="wizard-panel" data-step="1">
        <legend class="wizard-legend">
            <span class="wizard-legend-title">Registered details</span>
            <span class="wizard-legend-sub">
                These appear on the public company page under Governance, exactly as entered.
            </span>
        </legend>

        <div class="field-grid">
            <div class="field field-wide">
                <label for="f-name">Company name <span class="req">required</span></label>
                <input type="text" id="f-name" name="name" value="<?= e($company['name']) ?>" required>
            </div>

            <div class="field">
                <label for="f-reg">Registration number</label>
                <input type="text" id="f-reg" name="registration_number" value="<?= e($company['registration_number']) ?>">
            </div>

            <div class="field">
                <label for="f-inc">Incorporation date</label>
                <input type="date" id="f-inc" name="incorporation_date" value="<?= e($company['incorporation_date']) ?>" max="<?= date('Y-m-d') ?>">
            </div>

            <div class="field field-wide">
                <label for="f-addr">Registered address</label>
                <input type="text" id="f-addr" name="registered_address" value="<?= e($company['registered_address']) ?>">
            </div>

            <div class="field">
                <label for="f-secretary">Corporate secretary</label>
                <input type="text" id="f-secretary" name="corporate_secretary" value="<?= e($company['corporate_secretary']) ?>">
            </div>

            <div class="field">
                <label for="f-status">Listing status</label>
                <select id="f-status" name="status">
                    <option value="active"<?= $company['status'] === 'active' ? ' selected' : '' ?>>Active — visible on discovery</option>
                    <option value="inactive"<?= $company['status'] === 'inactive' ? ' selected' : '' ?>>Inactive — hidden</option>
                </select>
                <p class="field-help">
                    Inactive removes it from discovery and search. Existing shareholdings are
                    untouched.
                </p>
            </div>
        </div>
    </fieldset>

    <!-- ============================================================
         2. Listing copy and offer window
         ============================================================ -->
    <fieldset class="wizard-panel" data-step="2">
        <legend class="wizard-legend">
            <span class="wizard-legend-title">Listing copy and offer window</span>
            <span class="wizard-legend-sub">
                What an investor reads before deciding, and how long they have to decide.
            </span>
        </legend>

        <div class="field-grid">
            <div class="field field-wide">
                <label for="f-summary">Summary</label>
                <textarea id="f-summary" name="summary" rows="3" maxlength="600"><?= e($company['summary'] ?? '') ?></textarea>
                <p class="field-help">
                    Shown directly under the company name. Leave blank and the page writes its
                    own line from the asset and activity — accurate, but generic.
                </p>
            </div>

            <div class="field field-wide">
                <label for="f-case">Investment case</label>
                <textarea id="f-case" name="investment_case" rows="8"><?= e($company['investment_case'] ?? '') ?></textarea>
                <p class="field-help">
                    Appears in the Overview tab. Anything claimed here should be checkable
                    against the filed record below it.
                </p>
            </div>

            <div class="field">
                <label for="f-opens">Offer opens</label>
                <input type="datetime-local" id="f-opens" name="offer_opens_at"
                       value="<?= !empty($company['offer_opens_at']) ? e(date('Y-m-d\TH:i', strtotime($company['offer_opens_at']))) : '' ?>">
                <p class="field-help">Blank means it was open from the moment it was listed.</p>
            </div>

            <div class="field">
                <label for="f-closes">Offer closes</label>
                <input type="datetime-local" id="f-closes" name="offer_closes_at"
                       value="<?= !empty($company['offer_closes_at']) ? e(date('Y-m-d\TH:i', strtotime($company['offer_closes_at']))) : '' ?>">
                <p class="field-help">Blank means it stays open until the shares run out.</p>
            </div>
        </div>

        <div class="nav-callout">
            <i class="ti ti-clock" aria-hidden="true"></i>
            <div>
                <p class="nav-callout-title">The window is enforced, not decorative</p>
                <p class="muted">
                    Outside it, the commit button disappears and <code>/commit/<?= e($company['reference']) ?></code>
                    refuses the request. Setting a close date in the past will stop new
                    commitments immediately.
                </p>
            </div>
        </div>
    </fieldset>

    <!-- ============================================================
         3. Asset
         ============================================================ -->
    <fieldset class="wizard-panel" data-step="3">
        <legend class="wizard-legend">
            <span class="wizard-legend-title">The asset</span>
            <span class="wizard-legend-sub">
                <?php if ($asset): ?>
                    The valuation here sets the share price. Changing it moves NAV for every
                    existing shareholder, so check the Shares tab before saving.
                <?php else: ?>
                    No asset is on record for this company yet.
                <?php endif; ?>
            </span>
        </legend>

        <?php if ($asset): ?>
        <div class="field-grid">
            <div class="field field-wide">
                <label for="f-class">Asset class</label>
                <select id="f-class" name="asset_class_id">
                    <option value="">Unclassified</option>
                    <?php foreach ($assetClasses as $family => $classes): ?>
                        <optgroup label="<?= e($family) ?>">
                            <?php foreach ($classes as $ac): ?>
                                <option value="<?= (int) $ac['id'] ?>"<?= (int) $asset['asset_class_id'] === (int) $ac['id'] ? ' selected' : '' ?>>
                                    <?= e($ac['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($asset['asset_class_id'])): ?>
                    <p class="field-warn">
                        <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                        Unclassified, so this listing appears under no asset-class filter on discovery.
                    </p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="f-make">Make</label>
                <input type="text" id="f-make" name="make" value="<?= e($asset['make']) ?>">
            </div>

            <div class="field">
                <label for="f-model">Model</label>
                <input type="text" id="f-model" name="model" value="<?= e($asset['model']) ?>">
            </div>

            <div class="field">
                <label for="f-year">Year</label>
                <input type="number" id="f-year" name="year" min="1950" max="<?= (int) date('Y') + 1 ?>" value="<?= e($asset['year']) ?>">
            </div>

            <div class="field">
                <label for="f-mileage">Mileage (km)</label>
                <input type="number" id="f-mileage" name="mileage" min="0" value="<?= e($asset['mileage']) ?>">
            </div>

            <div class="field">
                <label for="f-vin">VIN</label>
                <input type="text" id="f-vin" name="vin" value="<?= e($asset['vin']) ?>" maxlength="50"
                       placeholder="Add once the unit is delivered">
                <p class="field-help">Optional, but unique across the platform when given.</p>
            </div>

            <div class="field">
                <label for="f-assetreg">Registration number</label>
                <input type="text" id="f-assetreg" name="asset_registration_number" value="<?= e($asset['registration_number']) ?>">
            </div>

            <div class="field">
                <label for="f-valuation">Current valuation</label>
                <div class="input-prefixed">
                    <span class="input-prefix">R</span>
                    <input type="number" id="f-valuation" name="current_valuation" step="0.01" min="0"
                           value="<?= e($asset['current_valuation']) ?>" data-nav-valuation>
                </div>
                <p class="field-help">Drives NAV per share.</p>
            </div>

            <div class="field">
                <label for="f-valuation-date">Valuation date</label>
                <input type="date" id="f-valuation-date" name="valuation_date" value="<?= e($asset['valuation_date']) ?>" max="<?= date('Y-m-d') ?>">
                <?php if (!empty($asset['valuation_date']) && strtotime($asset['valuation_date']) < strtotime('-12 months')): ?>
                    <p class="field-warn">
                        <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                        Over a year old. The public page is flagging NAV as indicative until it's refreshed.
                    </p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="f-insurance">Insurance status</label>
                <input type="text" id="f-insurance" name="insurance_status" value="<?= e($asset['insurance_status']) ?>" placeholder="Comprehensive, current">
            </div>

            <div class="field">
                <label for="f-roadworthy">Roadworthy status</label>
                <input type="text" id="f-roadworthy" name="roadworthy_status" value="<?= e($asset['roadworthy_status']) ?>" placeholder="Certified to Mar 2027">
            </div>

            <div class="field">
                <label for="f-asset-status">Asset status</label>
                <select id="f-asset-status" name="asset_status">
                    <option value="active"<?= $asset['asset_status'] === 'active' ? ' selected' : '' ?>>Active</option>
                    <option value="inactive"<?= $asset['asset_status'] === 'inactive' ? ' selected' : '' ?>>Inactive</option>
                    <option value="sold"<?= $asset['asset_status'] === 'sold' ? ' selected' : '' ?>>Sold</option>
                </select>
            </div>
        </div>
        <?php else: ?>
            <p class="muted">
                Nothing to edit here. An SPV without an asset can't be valued, so NAV stays
                at whatever it was set to.
            </p>
        <?php endif; ?>
    </fieldset>

    <!-- ============================================================
         4. Operation
         ============================================================ -->
    <fieldset class="wizard-panel" data-step="4">
        <legend class="wizard-legend">
            <span class="wizard-legend-title">How it earns</span>
            <span class="wizard-legend-sub">
                Changing the activity closes the current operating period and opens a new one,
                so the company's history stays intact. Editing the operator, area or
                utilisation updates the current period in place.
            </span>
        </legend>

        <div class="field-grid">
            <div class="field field-wide">
                <label for="f-activity">Commercial activity</label>
                <select id="f-activity" name="activity_type_id">
                    <option value="">Unclassified</option>
                    <?php foreach ($activityTypes as $sectorName => $types): ?>
                        <optgroup label="<?= e($sectorName) ?>">
                            <?php foreach ($types as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"<?= (int) ($activity['activity_type_id'] ?? 0) === (int) $t['id'] ? ' selected' : '' ?>>
                                    <?= e($t['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <?php if ($activity && empty($activity['activity_type_id'])): ?>
                    <p class="field-warn">
                        <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                        Recorded as &ldquo;<?= e($activity['activity_type']) ?>&rdquo; before the taxonomy
                        existed. It appears under no industry or activity filter until it's mapped.
                    </p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="f-operator">Operator</label>
                <input type="text" id="f-operator" name="operator" value="<?= e($activity['operator'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="f-location">Operating area</label>
                <input type="text" id="f-location" name="location" value="<?= e($activity['location'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="f-util">Utilisation rate</label>
                <div class="input-suffixed">
                    <input type="number" id="f-util" name="utilisation_rate" step="0.01" min="0" max="100"
                           value="<?= e($activity['utilisation_rate'] ?? '') ?>">
                    <span class="input-suffix">%</span>
                </div>
                <p class="field-help">Blank means not measured, which is different from zero.</p>
            </div>
        </div>
    </fieldset>

    <!-- ============================================================
         5. Shares and NAV
         ============================================================ -->
    <fieldset class="wizard-panel" data-step="5">
        <legend class="wizard-legend">
            <span class="wizard-legend-title">Shares and price</span>
            <span class="wizard-legend-sub">
                NAV per share is calculated from the asset valuation and the share count. It
                is not typed on this form, for the same reason it isn't on the create form.
            </span>
        </legend>

        <div class="field-grid">
            <div class="field">
                <label for="f-shares">Shares issued <span class="req">required</span></label>
                <input type="number" id="f-shares" name="shares_issued" min="<?= max(1, $committed) ?>" step="1"
                       value="<?= (int) $company['shares_issued'] ?>" required data-nav-shares>
                <p class="field-help">
                    <?php if ($committed > 0): ?>
                        <strong><?= number_format($committed) ?></strong> already held or committed,
                        so this can't go below that.
                    <?php else: ?>
                        Nothing has been taken up yet, so this is still free to change.
                    <?php endif; ?>
                </p>
            </div>

            <div class="field">
                <label for="f-nav">NAV per share <span class="derived">calculated</span></label>
                <div class="input-prefixed is-readonly">
                    <span class="input-prefix">R</span>
                    <input type="text" id="f-nav" value="<?= number_format($storedNav, 2) ?>" readonly tabindex="-1"
                           data-nav-output data-nav-current="<?= e(number_format($storedNav, 4, '.', '')) ?>"
                           aria-describedby="nav-explainer">
                </div>
                <p class="field-help" id="nav-explainer" data-nav-explainer>
                    Currently R<?= number_format($storedNav, 2) ?> a share.
                </p>
            </div>
        </div>

        <p class="nav-delta" data-nav-delta hidden></p>

        <div class="nav-callout">
            <i class="ti ti-calculator" aria-hidden="true"></i>
            <div>
                <p class="nav-callout-title">What moving NAV actually does</p>
                <p class="muted">
                    NAV per share is the asset's value divided by the shares issued, and every
                    percentage on the platform is built on it &mdash; the funding target, the
                    progress bar, the trailing yield. Revaluing the asset re-prices the
                    <em>whole</em> company, including shares already on the register: existing
                    holders keep their share count and the value of each one changes. That is
                    how it should work, but it isn't reversible by editing a text box, so the
                    number comes from the two figures that define it rather than from typing.
                </p>
                <?php if ($purchasePrice > 0): ?>
                    <p class="muted" style="margin-top:0.6rem;">
                        If the valuation is cleared, the recorded purchase price of
                        R<?= number_format($purchasePrice, 2) ?> is used instead.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </fieldset>

    <div class="save-bar">
        <p class="save-bar-status muted" data-dirty-status>No unsaved changes.</p>
        <a href="/admin/companies" class="btn-outline">Cancel</a>
        <button type="submit" class="btn"><i class="ti ti-device-floppy" aria-hidden="true"></i> Save changes</button>
    </div>
</form>

<script src="/assets/js/admin-form-steps.js" defer></script>
