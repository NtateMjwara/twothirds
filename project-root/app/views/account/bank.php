<?php
$currentTab = 'bank';
require __DIR__ . '/../partials/_account-header.php';
use app\services\ProfileOptions;

$old = $old ?? [];
$profileName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
?>
<?php if (!empty($error)): ?><p class="form-error"><i class="ti ti-alert-circle" aria-hidden="true"></i> <?= e($error) ?></p><?php endif; ?>

<div class="account-panel">
    <h2 class="panel-title">Bank accounts</h2>
    <p class="panel-sub muted">
        Without a verified bank account you can't be paid a distribution. Everything else on
        the platform works without one.
    </p>

    <div class="callout callout-warn">
        <i class="ti ti-shield-lock" aria-hidden="true"></i>
        <p>
            <strong>The account must be in your own name.</strong>
            Payouts only ever go to an account matching the name on this profile<?php
            if ($profileName !== ''): ?> &mdash; <?= e($profileName) ?><?php endif; ?>.
            Third-party accounts are refused, which is the single most effective control
            against someone else's account being substituted for yours.
        </p>
    </div>

    <h3 class="section-title">Your accounts</h3>

    <?php if (empty($accounts)): ?>
        <div class="empty-state">
            <div class="asset-icon"><i class="ti ti-building-bank" aria-hidden="true"></i></div>
            <h3>No accounts yet</h3>
            <p class="muted">Add one below. It can be verified while you carry on browsing.</p>
        </div>
    <?php else: ?>
        <ul class="bank-list">
            <?php foreach ($accounts as $account): ?>
                <li class="bank-row<?= (int) $account['is_primary'] === 1 ? ' is-primary' : '' ?>">
                    <div class="bank-identity">
                        <span class="bank-number is-mono"><?= e($account['masked']) ?></span>
                        <span class="bank-name muted">
                            <?= e($account['bank_name']) ?> &middot;
                            <?= e(ProfileOptions::accountTypes()[$account['account_type']] ?? $account['account_type']) ?>
                        </span>
                    </div>

                    <span class="bank-currency"><?= e($account['currency']) ?></span>

                    <span class="bank-status">
                        <?php if ($account['status'] === 'verified'): ?>
                            <span class="verify-pill is-verified"><i class="ti ti-circle-check" aria-hidden="true"></i> Verified</span>
                        <?php elseif ($account['status'] === 'rejected'): ?>
                            <span class="verify-pill is-rejected"><i class="ti ti-alert-circle" aria-hidden="true"></i> Rejected</span>
                        <?php else: ?>
                            <span class="verify-pill is-pending"><i class="ti ti-clock" aria-hidden="true"></i> Pending</span>
                        <?php endif; ?>
                    </span>

                    <span class="bank-primary">
                        <?php if ((int) $account['is_primary'] === 1): ?>
                            <span class="primary-flag"><i class="ti ti-star-filled" aria-hidden="true"></i> Payouts go here</span>
                        <?php elseif ($account['status'] === 'verified'): ?>
                            <form method="post" action="/account/bank/<?= (int) $account['id'] ?>/primary">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn-compact">Use for payouts</button>
                            </form>
                        <?php endif; ?>
                    </span>

                    <form method="post" action="/account/bank/<?= (int) $account['id'] ?>/delete" class="bank-remove"
                          onsubmit="return confirm('Remove this bank account? You can add it again later, but it will need verifying afresh.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="icon-button" aria-label="Remove this account">
                            <i class="ti ti-trash" aria-hidden="true"></i>
                        </button>
                    </form>

                    <?php if ($account['status'] === 'rejected' && !empty($account['rejection_reason'])): ?>
                        <p class="bank-reason">Rejected: <?= e($account['rejection_reason']) ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p class="muted small-note">
        You can hold up to <?= ProfileOptions::MAX_ACCOUNTS_PER_CURRENCY ?> accounts per currency.
    </p>

    <?php if ($atLimit): ?>
        <div class="callout callout-plain">
            <i class="ti ti-info-circle" aria-hidden="true"></i>
            <p>You've reached the limit for ZAR accounts. Remove one before adding another.</p>
        </div>
    <?php else: ?>
        <h3 class="section-title">Add an account</h3>

        <form method="post" action="/account/bank" data-bank-form>
            <?= csrf_field() ?>

            <div class="field-grid">
                <div class="field field-wide">
                    <label for="b-holder">Account holder name <span class="req">required</span></label>
                    <input type="text" id="b-holder" name="account_holder_name" required
                           value="<?= e($old['account_holder_name'] ?? $profileName) ?>">
                    <p class="field-help">Exactly as it appears on your bank statement.</p>
                </div>

                <div class="field">
                    <label for="b-bank">Bank <span class="req">required</span></label>
                    <select id="b-bank" name="bank_name" required data-bank-select>
                        <option value="">Select&hellip;</option>
                        <?php foreach (ProfileOptions::banks() as $bank => $branch): ?>
                            <option value="<?= e($bank) ?>" data-branch="<?= e($branch) ?>"
                                <?= ($old['bank_name'] ?? '') === $bank ? ' selected' : '' ?>><?= e($bank) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="b-branch">Branch code</label>
                    <input type="text" id="b-branch" name="branch_code" inputmode="numeric" maxlength="10"
                           value="<?= e($old['branch_code'] ?? '') ?>" data-branch-input>
                    <p class="field-help">Filled in with the bank's universal code. Change it if yours differs.</p>
                </div>

                <div class="field">
                    <label for="b-number">Account number <span class="req">required</span></label>
                    <input type="text" id="b-number" name="account_number" required inputmode="numeric"
                           maxlength="20" autocomplete="off">
                    <p class="field-help">Digits only, no spaces. Stored encrypted.</p>
                </div>

                <div class="field">
                    <label for="b-type">Account type</label>
                    <select id="b-type" name="account_type">
                        <?php foreach (ProfileOptions::accountTypes() as $key => $label): ?>
                            <option value="<?= e($key) ?>"<?= ($old['account_type'] ?? '') === $key ? ' selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="panel-actions">
                <button type="submit" class="btn"><i class="ti ti-plus" aria-hidden="true"></i> Add account</button>
            </div>
        </form>
    <?php endif; ?>

    <details class="section-explainer bank-guidelines">
        <summary><i class="ti ti-info-circle" aria-hidden="true"></i> Bank detail guidelines</summary>
        <div>
            <ul>
                <li>Allow up to 48 hours for a new account to be verified.</li>
                <li>The name on the account must match the name on your TwoThirds profile.</li>
                <li>Joint accounts are accepted only where you are one of the named holders.</li>
                <li>Business accounts can't be used for a personal investment account.</li>
                <li>An account that fails verification is marked rejected with a reason, and no
                    payout is ever attempted to it.</li>
            </ul>
        </div>
    </details>
</div>

<script src="/assets/js/account-forms.js" defer></script>
