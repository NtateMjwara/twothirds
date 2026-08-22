<?php
$currentTab = 'banking';
require __DIR__ . '/../partials/_company-tabs.php';

$old = $old ?? [];
$v = static fn (string $key, string $default = ''): string
    => e((string) ($old[$key] ?? $account[$key] ?? $default));
?>
<?php if (!empty($error)): ?>
    <p class="form-error"><i class="ti ti-alert-circle" aria-hidden="true"></i> <?= e($error) ?></p>
<?php endif; ?>

<?php if (!$complete): ?>
    <p class="notice notice-warn">
        <i class="ti ti-alert-triangle" aria-hidden="true"></i>
        <strong>No banking details on file.</strong>
        Investors can browse this offering but cannot commit to it &mdash; an invoice with
        nowhere to pay would be worse than a blocked button, so the commit flow refuses until
        this is filled in.
    </p>
<?php endif; ?>

<div class="settings-card" style="max-width:640px;">
    <h2 style="margin-top:0;">Where investors pay</h2>
    <p class="muted" style="font-size:0.88rem;">
        This is <?= e($company['name']) ?>'s own bank account. It appears on investor invoices
        and nowhere else &mdash; never on the public company page, never in a listing query.
        The account number is encrypted before it is stored and is never shown back in full,
        including here.
    </p>

    <form method="post" action="/admin/companies/<?= e($company['reference']) ?>/banking">
        <?= csrf_field() ?>

        <div class="field-grid">
            <div class="field field-wide">
                <label for="b-holder">Account holder <span class="req">required</span></label>
                <input type="text" id="b-holder" name="account_holder_name" required
                       value="<?= $v('account_holder_name', $company['name']) ?>">
                <p class="field-help">
                    Should be the SPV itself, as the bank has it registered. Investors are told
                    to pay this name, so a mismatch stalls payments.
                </p>
            </div>

            <div class="field">
                <label for="b-bank">Bank <span class="req">required</span></label>
                <select id="b-bank" name="bank_name" required data-bank-select>
                    <option value="">Select&hellip;</option>
                    <?php foreach ($banks as $bank => $branch): ?>
                        <option value="<?= e($bank) ?>" data-branch="<?= e($branch) ?>"
                            <?= ($old['bank_name'] ?? $account['bank_name'] ?? '') === $bank ? ' selected' : '' ?>>
                            <?= e($bank) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="b-branch">Branch code</label>
                <input type="text" id="b-branch" name="branch_code" inputmode="numeric" maxlength="10"
                       value="<?= $v('branch_code') ?>" data-branch-input>
                <p class="field-help">Filled in with the bank's universal code. Change it if yours differs.</p>
            </div>

            <div class="field">
                <label for="b-number">
                    Account number
                    <?php if (!$account): ?><span class="req">required</span><?php endif; ?>
                </label>
                <input type="text" id="b-number" name="account_number" inputmode="numeric"
                       maxlength="20" autocomplete="off"
                       placeholder="<?= $numberHint ? 'On file, ending ' . e($numberHint) : 'Digits only' ?>">
                <p class="field-help">
                    <?php if ($numberHint): ?>
                        Stored and encrypted. Leave blank to keep it unchanged.
                    <?php else: ?>
                        Digits only, no spaces.
                    <?php endif; ?>
                </p>
            </div>

            <div class="field">
                <label for="b-type">Account type</label>
                <select id="b-type" name="account_type">
                    <option value="cheque"<?= ($account['account_type'] ?? 'cheque') === 'cheque' ? ' selected' : '' ?>>Cheque / current</option>
                    <option value="savings"<?= ($account['account_type'] ?? '') === 'savings' ? ' selected' : '' ?>>Savings</option>
                </select>
            </div>

            <div class="field">
                <label for="b-prefix">Payment reference prefix</label>
                <input type="text" id="b-prefix" name="reference_prefix" maxlength="8"
                       value="<?= $v('reference_prefix') ?>" placeholder="Optional">
                <p class="field-help">
                    Prepended to the commitment reference an investor quotes. Useful when several
                    SPVs bank with the same institution and one statement covers them all.
                    Keep it short &mdash; bank reference fields hold about 20 characters, and the
                    commitment reference takes priority, so a long prefix is trimmed or dropped
                    rather than allowed to eat into it.
                </p>
            </div>

            <div class="field">
                <label for="b-swift">SWIFT code</label>
                <input type="text" id="b-swift" name="swift_code" maxlength="20" value="<?= $v('swift_code') ?>">
                <p class="field-help">Only needed if you expect payment from outside South Africa.</p>
            </div>
        </div>

        <div class="panel-actions">
            <button type="submit" class="btn"><i class="ti ti-device-floppy" aria-hidden="true"></i> Save banking details</button>
        </div>
    </form>
</div>

<script src="/assets/js/account-forms.js" defer></script>
