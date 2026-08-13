<h1>Create a new SPV</h1>
<?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>

<div class="settings-card" style="max-width:640px;">
    <form method="post" action="/admin/companies">
        <?= csrf_field() ?>

        <h2 style="margin-top:0;">Company</h2>
        <label>Name<input type="text" name="name" required></label>
        <label>Registration number<input type="text" name="registration_number"></label>
        <label>Incorporation date<input type="date" name="incorporation_date"></label>
        <label>Registered address<input type="text" name="registered_address"></label>
        <label>Shares issued<input type="number" name="shares_issued" required></label>
        <label>NAV per share<input type="number" step="0.0001" name="nav_per_share" value="1.00" required></label>

        <h2>Asset</h2>
        <label>Asset class
            <select name="asset_class_id" required>
                <option value="">Select a class&hellip;</option>
                <?php foreach ($assetClasses as $family => $classes): ?>
                    <optgroup label="<?= e($family) ?>">
                        <?php foreach ($classes as $ac): ?>
                            <option value="<?= (int) $ac['id'] ?>"><?= e($ac['name']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </label>
        <p class="muted" style="font-size:0.82rem; margin-top:-0.4rem;">
            The class drives the discovery filters. Make and model stay free text for the
            specific unit.
        </p>
        <label>Make<input type="text" name="make" required placeholder="Toyota"></label>
        <label>Model<input type="text" name="model" required placeholder="Quantum 2.8 GL"></label>
        <label>Year<input type="number" name="year"></label>
        <label>VIN<input type="text" name="vin" required></label>
        <label>Registration number<input type="text" name="asset_registration_number"></label>
        <label>Purchase price<input type="number" step="0.01" name="purchase_price"></label>
        <label>Purchase date<input type="date" name="purchase_date"></label>
        <label>Current valuation<input type="number" step="0.01" name="current_valuation"></label>
        <label>Valuation date<input type="date" name="valuation_date"></label>
        <label>Mileage (km)<input type="number" name="mileage"></label>

        <h2>Commercial activity</h2>
        <label>Activity
            <select name="activity_type_id" required>
                <option value="">Select an activity&hellip;</option>
                <?php foreach ($activityTypes as $sectorName => $types): ?>
                    <optgroup label="<?= e($sectorName) ?>">
                        <?php foreach ($types as $t): ?>
                            <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </label>
        <p class="muted" style="font-size:0.82rem; margin-top:-0.4rem;">
            The industry comes from the activity, so an SPV is only ever in one sector.
        </p>
        <label>Operator<input type="text" name="operator" placeholder="Trading name of the operator"></label>
        <label>Operating area<input type="text" name="location" placeholder="Soweto, Gauteng"></label>
        <label>Utilisation rate (%)<input type="number" step="0.01" min="0" max="100" name="utilisation_rate"></label>
        <label>Start date<input type="date" name="activity_start_date" value="<?= date('Y-m-d') ?>"></label>

        <button type="submit" class="btn">Create SPV</button>
    </form>
</div>
