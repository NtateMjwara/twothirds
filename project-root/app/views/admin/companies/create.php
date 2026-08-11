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
        <label>Make<input type="text" name="make" required></label>
        <label>Model<input type="text" name="model" required></label>
        <label>Year<input type="number" name="year"></label>
        <label>VIN<input type="text" name="vin" required></label>
        <label>Registration number<input type="text" name="asset_registration_number"></label>
        <label>Purchase price<input type="number" step="0.01" name="purchase_price"></label>
        <label>Purchase date<input type="date" name="purchase_date"></label>
        <label>Current valuation<input type="number" step="0.01" name="current_valuation"></label>
        <label>Valuation date<input type="date" name="valuation_date"></label>
        <label>Mileage (km)<input type="number" name="mileage"></label>

        <h2>Commercial activity</h2>
        <label>Activity type<input type="text" name="activity_type" placeholder="Uber"></label>
        <label>Operator<input type="text" name="operator"></label>
        <label>Location<input type="text" name="location"></label>
        <label>Start date<input type="date" name="activity_start_date"></label>

        <button type="submit" class="btn">Create SPV</button>
    </form>
</div>
