<?php
$currentTab = 'kyc';
require __DIR__ . '/../partials/_account-header.php';
use app\services\ProfileOptions;

$old = $old ?? null;
$val = static function (string $key) use ($kyc, $old): string {
    $source = $old ?? $kyc ?? [];
    return e((string) ($source[$key] ?? ''));
};
$sel = static function (string $key, string $value) use ($kyc, $old): string {
    $source = $old ?? $kyc ?? [];
    return (string) ($source[$key] ?? '') === $value ? ' selected' : '';
};

$status = $kyc['status'] ?? null;
$isVerified = $status === 'verified';
?>
<?php if (!empty($error)): ?><p class="form-error"><i class="ti ti-alert-circle" aria-hidden="true"></i> <?= e($error) ?></p><?php endif; ?>

<div class="account-panel">
    <h2 class="panel-title">Know your client (KYC / FICA)</h2>

    <div class="kyc-status-row">
        <span class="kyc-status-label">Current status</span>
        <?php if ($isVerified): ?>
            <span class="verify-pill is-verified"><i class="ti ti-circle-check" aria-hidden="true"></i> Verified</span>
        <?php elseif ($status === 'pending'): ?>
            <span class="verify-pill is-pending"><i class="ti ti-clock" aria-hidden="true"></i> In review</span>
        <?php elseif ($status === 'rejected'): ?>
            <span class="verify-pill is-rejected"><i class="ti ti-alert-circle" aria-hidden="true"></i> Not approved</span>
        <?php else: ?>
            <span class="verify-pill is-none"><i class="ti ti-minus" aria-hidden="true"></i> Not submitted</span>
        <?php endif; ?>

        <?php if (!empty($kyc['document_id'])): ?>
            <a href="/documents/<?= (int) $kyc['document_id'] ?>" target="_blank" rel="noopener" class="kyc-doc-link">
                <i class="ti ti-file-search" aria-hidden="true"></i> View what you submitted
            </a>
        <?php endif; ?>
    </div>

    <?php if ($status === 'rejected' && !empty($kyc['rejection_reason'])): ?>
        <div class="callout callout-warn">
            <i class="ti ti-alert-triangle" aria-hidden="true"></i>
            <p>
                <strong>Not approved:</strong> <?= e($kyc['rejection_reason']) ?><br>
                Correct what's described above and submit again.
            </p>
        </div>
    <?php elseif ($status === 'pending'): ?>
        <div class="callout callout-plain">
            <i class="ti ti-clock" aria-hidden="true"></i>
            <p>
                Your documents are with our team. Verification usually takes a working day or
                two, and you'll be notified either way. You can still update the details below
                while you wait.
            </p>
        </div>
    <?php endif; ?>

    <form method="post" action="/account/kyc" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <h3 class="section-title">Identity</h3>
        <?php if ($isVerified): ?>
            <div class="callout callout-plain">
                <i class="ti ti-lock" aria-hidden="true"></i>
                <p>
                    Your identity is verified, so these are locked. If an ID number or document
                    genuinely needs to change, contact support &mdash; it has to be re-reviewed
                    rather than edited.
                </p>
            </div>
            <dl class="readonly-grid">
                <div>
                    <dt>ID type</dt>
                    <dd><?= ($kyc['id_type'] ?? '') === 'passport' ? 'Passport' : 'South African ID' ?></dd>
                </div>
                <div>
                    <dt>ID number</dt>
                    <dd class="is-mono">
                        <?php $n = (string) ($kyc['id_number'] ?? ''); ?>
                        <?= e(str_repeat('•', max(0, strlen($n) - 4)) . substr($n, -4)) ?>
                    </dd>
                </div>
            </dl>
        <?php else: ?>
            <div class="field-grid">
                <div class="field">
                    <label for="k-type">ID type <span class="req">required</span></label>
                    <select id="k-type" name="id_type">
                        <option value="sa_id"<?= $sel('id_type', 'sa_id') ?>>South African ID</option>
                        <option value="passport"<?= $sel('id_type', 'passport') ?>>Passport</option>
                    </select>
                </div>

                <div class="field">
                    <label for="k-number">ID or passport number <span class="req">required</span></label>
                    <input type="text" id="k-number" name="id_number" value="<?= $val('id_number') ?>" required
                           inputmode="numeric" maxlength="50">
                    <p class="field-help">A South African ID number is 13 digits.</p>
                </div>

                <div class="field field-wide">
                    <label for="k-doc">
                        Upload your ID or passport
                        <?php if (empty($kyc['document_id'])): ?><span class="req">required</span><?php endif; ?>
                    </label>
                    <input type="file" id="k-doc" name="id_document" accept=".pdf,.jpg,.jpeg,.png">
                    <p class="field-help">
                        PDF, JPG or PNG, up to 5MB. A clear photo of the whole document &mdash; all
                        four corners visible, nothing cropped or covered.
                        <?php if (!empty($kyc['document_id'])): ?>
                            Leave blank to keep the copy already on file.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <h3 class="section-title">Employment and income</h3>
        <p class="panel-sub muted">
            Required under FICA. It sets the risk rating on your account and is not shared
            outside our compliance process.
        </p>

        <div class="field-grid">
            <div class="field">
                <label for="k-income">Source of income <span class="req">required</span></label>
                <p class="field-help field-help-lead">What would you classify as your primary source of income?</p>
                <select id="k-income" name="source_of_income" required>
                    <option value="">Select&hellip;</option>
                    <?php foreach (ProfileOptions::incomeSources() as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $sel('source_of_income', $key) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="k-funds">Account funds <span class="req">required</span></label>
                <p class="field-help field-help-lead">Where will the money you invest come from?</p>
                <select id="k-funds" name="account_funds_source" required>
                    <option value="">Select&hellip;</option>
                    <?php foreach (ProfileOptions::fundSources() as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $sel('account_funds_source', $key) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="k-occupation">Occupation <span class="req">required</span></label>
                <p class="field-help field-help-lead">What is your employment status?</p>
                <select id="k-occupation" name="occupation" required data-employment>
                    <option value="">Select&hellip;</option>
                    <?php foreach (ProfileOptions::occupations() as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $sel('occupation', $key) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="k-band">Total annual income <span class="req">required</span></label>
                <p class="field-help field-help-lead">Salary, side income, rental and investments combined.</p>
                <select id="k-band" name="annual_income_band" required>
                    <option value="">Select&hellip;</option>
                    <?php foreach (ProfileOptions::incomeBands() as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $sel('annual_income_band', $key) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="k-employer">Employer</label>
                <input type="text" id="k-employer" name="employer" value="<?= $val('employer') ?>">
                <p class="field-help">Required if you're employed or a company director.</p>
            </div>

            <div class="field">
                <label for="k-industry">Industry <span class="req">required</span></label>
                <select id="k-industry" name="industry" required>
                    <option value="">Select&hellip;</option>
                    <?php foreach (ProfileOptions::industries() as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $sel('industry', $key) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="panel-actions">
            <button type="submit" class="btn">
                <i class="ti ti-shield-check" aria-hidden="true"></i>
                <?= $isVerified ? 'Update details' : 'Submit for verification' ?>
            </button>
        </div>
    </form>
</div>
