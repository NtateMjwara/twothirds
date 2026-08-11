<p><a href="/company/<?= e($company['reference']) ?>">&larr; Back to <?= e($company['name']) ?></a></p>
<div class="page-title-row">
    <h1>Edit <?= e($company['name']) ?></h1>
    <span class="ref-badge"><?= e($company['reference']) ?></span>
</div>
<?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>

<div class="settings-card" style="max-width:640px;">
    <form method="post" action="/admin/companies/<?= e($company['reference']) ?>/edit">
        <?= csrf_field() ?>

        <h2 style="margin-top:0;">Company</h2>
        <label>Name<input type="text" name="name" value="<?= e($company['name']) ?>" required></label>
        <label>Registration number<input type="text" name="registration_number" value="<?= e($company['registration_number']) ?>"></label>
        <label>Incorporation date<input type="date" name="incorporation_date" value="<?= e($company['incorporation_date']) ?>"></label>
        <label>Registered address<input type="text" name="registered_address" value="<?= e($company['registered_address']) ?>"></label>
        <label>Corporate secretary<input type="text" name="corporate_secretary" value="<?= e($company['corporate_secretary']) ?>"></label>
        <label>Status
            <select name="status">
                <option value="active" <?= $company['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $company['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </label>
        <label>Shares issued<input type="number" name="shares_issued" value="<?= (int) $company['shares_issued'] ?>" required></label>
        <label>NAV per share<input type="number" step="0.0001" name="nav_per_share" value="<?= e($company['nav_per_share']) ?>" required></label>

        <?php if ($asset): ?>
        <h2>Asset</h2>
        <label>Make<input type="text" name="make" value="<?= e($asset['make']) ?>"></label>
        <label>Model<input type="text" name="model" value="<?= e($asset['model']) ?>"></label>
        <label>Year<input type="number" name="year" value="<?= e($asset['year']) ?>"></label>
        <label>VIN<input type="text" name="vin" value="<?= e($asset['vin']) ?>"></label>
        <label>Registration number<input type="text" name="asset_registration_number" value="<?= e($asset['registration_number']) ?>"></label>
        <label>Current valuation<input type="number" step="0.01" name="current_valuation" value="<?= e($asset['current_valuation']) ?>"></label>
        <label>Valuation date<input type="date" name="valuation_date" value="<?= e($asset['valuation_date']) ?>"></label>
        <label>Mileage (km)<input type="number" name="mileage" value="<?= e($asset['mileage']) ?>"></label>
        <label>Insurance status<input type="text" name="insurance_status" value="<?= e($asset['insurance_status']) ?>"></label>
        <label>Roadworthy status<input type="text" name="roadworthy_status" value="<?= e($asset['roadworthy_status']) ?>"></label>
        <label>Asset status
            <select name="asset_status">
                <option value="active" <?= $asset['asset_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $asset['asset_status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                <option value="sold" <?= $asset['asset_status'] === 'sold' ? 'selected' : '' ?>>Sold</option>
            </select>
        </label>
        <?php else: ?>
        <p class="muted">No asset on record for this company yet.</p>
        <?php endif; ?>

        <button type="submit" class="btn">Save changes</button>
    </form>
</div>
