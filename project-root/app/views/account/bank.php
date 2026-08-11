<?php require __DIR__ . '/../partials/account-nav.php'; ?>
<h1>Bank account</h1>
<?php if (!empty($saved)): ?><p class="form-success">Saved.</p><?php endif; ?>

<?php if ($account): ?>
<div class="settings-card" style="padding:1rem 1.75rem;">
    <p class="muted" style="margin:0;">On file: <?= e($account['bank_name']) ?> &middot; ****<?= e($maskedNumber) ?></p>
</div>
<?php endif; ?>

<div class="settings-card">
    <form method="post" action="/account/bank">
        <?= csrf_field() ?>
        <label>Account holder name<input type="text" name="account_holder_name" value="<?= e($account['account_holder_name'] ?? '') ?>" required></label>
        <label>Bank name<input type="text" name="bank_name" value="<?= e($account['bank_name'] ?? '') ?>" required></label>
        <label>Account number<?= $account ? ' (leave blank to keep current)' : '' ?>
            <input type="text" name="account_number" placeholder="<?= $account ? 'Unchanged' : '' ?>" <?= $account ? '' : 'required' ?>>
        </label>
        <label>Branch code<input type="text" name="branch_code" value="<?= e($account['branch_code'] ?? '') ?>"></label>
        <label>Account type
            <select name="account_type">
                <option value="cheque" <?= ($account['account_type'] ?? '') === 'cheque' ? 'selected' : '' ?>>Cheque/current</option>
                <option value="savings" <?= ($account['account_type'] ?? '') === 'savings' ? 'selected' : '' ?>>Savings</option>
            </select>
        </label>
        <button type="submit" class="btn">Save bank account</button>
    </form>
</div>
