<?php
/**
 * After a successful commitment.
 *
 * The banking details are shown here as well as emailed. An investor who closes
 * the tab and never finds the email should still be able to pay - and support
 * requests for "where do I send the money" are otherwise the most common thing
 * this page causes.
 */
?>
<div class="confirm-wrap">
    <div class="confirm-head">
        <span class="confirm-icon"><i class="ti ti-circle-check" aria-hidden="true"></i></span>
        <h1>Your shares are reserved</h1>
        <p class="muted">
            Commitment <strong><?= e($reference) ?></strong>
            <?php if ($invoice): ?>
                &middot; invoice <strong><?= e($invoice['invoice_number']) ?></strong>
            <?php endif; ?>
        </p>
    </div>

    <?php if (!empty($error)): ?>
        <p class="form-error"><i class="ti ti-alert-circle" aria-hidden="true"></i> <?= e($error) ?></p>
    <?php endif; ?>

    <?php if ($invoice): ?>
        <div class="invoice-card">
            <div class="invoice-section">
                <h2>What you owe</h2>
                <table class="fee-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?= number_format($invoice['shares']) ?> shares at R<?= number_format($invoice['nav'], 2) ?></th>
                            <td>R<?= number_format($invoice['share_amount'], 2) ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Transaction fee (<?= rtrim(rtrim(number_format($invoice['fee_rate'] * 100, 1), '0'), '.') ?>%)</th>
                            <td class="is-out">R<?= number_format($invoice['fee_amount'], 2) ?></td>
                        </tr>
                        <tr class="is-total">
                            <th scope="row">Total due</th>
                            <td>R<?= number_format($invoice['total_due'], 2) ?></td>
                        </tr>
                    </tbody>
                </table>
                <?php if (!empty($invoice['due_at'])): ?>
                    <p class="muted small-note">
                        Payable by <?= e(date('j F Y', strtotime($invoice['due_at']))) ?>. After that the
                        commitment lapses and the shares return to the available pool.
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($invoice['bank']): ?>
                <div class="invoice-section invoice-bank">
                    <h2>Where to pay</h2>
                    <dl class="bank-detail-grid">
                        <div><dt>Account holder</dt><dd><?= e($invoice['bank']['account_holder']) ?></dd></div>
                        <div><dt>Bank</dt><dd><?= e($invoice['bank']['bank_name']) ?></dd></div>
                        <div><dt>Account number</dt><dd class="is-mono"><?= e($invoice['bank']['account_number']) ?></dd></div>
                        <div><dt>Branch code</dt><dd class="is-mono"><?= e($invoice['bank']['branch_code']) ?></dd></div>
                        <div><dt>Account type</dt><dd><?= e(ucfirst($invoice['bank']['account_type'])) ?></dd></div>
                        <?php if (!empty($invoice['bank']['swift_code'])): ?>
                            <div><dt>SWIFT</dt><dd class="is-mono"><?= e($invoice['bank']['swift_code']) ?></dd></div>
                        <?php endif; ?>
                    </dl>

                    <!-- The single most important field on the page: without it
                         a deposit can't be matched to a commitment. -->
                    <div class="payment-reference">
                        <span class="payment-reference-label">Use this reference</span>
                        <span class="payment-reference-value is-mono"><?= e($invoice['payment_reference']) ?></span>
                        <span class="payment-reference-note muted">
                            Payments without it can't be matched to your commitment and will delay settlement.
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <div class="invoice-section">
                    <p class="notice notice-warn">
                        <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                        We couldn't attach this company's banking details. Contact us quoting
                        <strong><?= e($reference) ?></strong> and we'll send them.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <p class="muted confirm-email-note">
            <i class="ti ti-mail" aria-hidden="true"></i>
            A copy has been emailed to <?= e($invoice['investor']['email']) ?>, along with the
            documents you accepted.
        </p>
    <?php endif; ?>

    <div class="confirm-actions">
        <a href="/account/portfolio" class="btn"><i class="ti ti-chart-pie" aria-hidden="true"></i> View your portfolio</a>
        <a href="<?= e(invest_url()) ?>" class="btn-outline">Browse more offerings</a>
    </div>
</div>
