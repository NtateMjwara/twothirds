<?php
/**
 * Commitment form.
 *
 * The consent block is deliberately not a single "I agree to the terms" tick.
 * Buying shares in a private company means accepting several distinct
 * instruments, and one checkbox covering all of them records almost nothing
 * about what the investor actually saw.
 */
$old = $old ?? [];
$shares = (int) ($old['shares'] ?? 0);
$nav = (float) $company['nav_per_share'];
$feePct = $feeRate * 100;
$accepted = array_map('strval', (array) ($old['agree'] ?? []));
?>
<p class="admin-breadcrumb">
    <a href="<?= e(company_url($company)) ?>">
        <i class="ti ti-chevron-left" aria-hidden="true"></i> Back to <?= e($company['name']) ?>
    </a>
</p>

<div class="commit-layout">
    <div class="commit-main">
        <h1>Commit to <?= e($company['name']) ?></h1>
        <p class="muted commit-lede">
            This reserves shares at today's NAV and produces an invoice. No money is taken
            now &mdash; you'll be sent banking details and have until the commitment expires
            to pay.
        </p>

        <?php if (!empty($error)): ?>
            <p class="form-error"><i class="ti ti-alert-circle" aria-hidden="true"></i> <?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" action="/commit/<?= e($company['reference']) ?>" id="commitForm">
            <?= csrf_field() ?>

            <div class="commit-panel">
                <h2 class="panel-title">How many shares</h2>

                <div class="field">
                    <label for="c-shares">Number of shares <span class="req">required</span></label>
                    <input type="number" id="c-shares" name="shares" min="1" max="<?= (int) $available ?>"
                           step="1" required value="<?= $shares > 0 ? $shares : '' ?>"
                           data-shares data-nav="<?= e(number_format($nav, 4, '.', '')) ?>"
                           data-fee="<?= e((string) $feeRate) ?>">
                    <p class="field-help">
                        <?= number_format($available) ?> available at
                        R<?= number_format($nav, 2) ?> a share.
                    </p>
                </div>

                <!-- Priced live, and again on the server. The figure below is a
                     preview; the invoice is what counts. -->
                <table class="fee-table commit-total" data-quote hidden>
                    <tbody>
                        <tr>
                            <th scope="row"><span data-quote-shares>0</span> shares at R<?= number_format($nav, 2) ?></th>
                            <td data-quote-subtotal>R0.00</td>
                        </tr>
                        <tr>
                            <th scope="row">Transaction fee (<?= rtrim(rtrim(number_format($feePct, 1), '0'), '.') ?>%)</th>
                            <td class="is-out" data-quote-fee>R0.00</td>
                        </tr>
                        <tr class="is-total">
                            <th scope="row">Total payable</th>
                            <td data-quote-total>R0.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="commit-panel">
                <h2 class="panel-title">What you're agreeing to</h2>
                <p class="panel-sub muted">
                    Open each one. They're short, and they're the actual terms of what you're
                    buying &mdash; not boilerplate.
                </p>

                <ul class="agreement-list">
                    <?php foreach ($documents as $doc): ?>
                        <li class="agreement-item">
                            <label>
                                <input type="checkbox" name="agree[]" value="<?= (int) $doc['id'] ?>"
                                       <?= in_array((string) $doc['id'], $accepted, true) ? 'checked' : '' ?>
                                       <?= (int) $doc['is_required'] === 1 ? 'required' : '' ?>
                                       data-agreement>
                                <span class="agreement-text">
                                    <span class="agreement-title">
                                        I have read and accept the <?= e($doc['title']) ?>
                                        <?php if ((int) $doc['is_required'] === 1): ?>
                                            <span class="req">required</span>
                                        <?php else: ?>
                                            <span class="optional">optional</span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if (!empty($doc['summary'])): ?>
                                        <span class="agreement-summary muted"><?= e($doc['summary']) ?></span>
                                    <?php endif; ?>
                                    <a class="agreement-link" href="/legal/<?= e($doc['doc_key']) ?>?company=<?= e($company['reference']) ?>"
                                       target="_blank" rel="noopener">
                                        Read it <i class="ti ti-external-link" aria-hidden="true"></i>
                                        <span class="agreement-version">v<?= e($doc['version']) ?></span>
                                    </a>
                                </span>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <p class="muted agreement-note">
                    We record which version of each document you accepted and when. You'll get a
                    copy with your invoice.
                </p>
            </div>

            <div class="commit-actions">
                <button type="submit" class="btn">
                    <i class="ti ti-file-invoice" aria-hidden="true"></i> Commit and get my invoice
                </button>
                <a href="<?= e(company_url($company)) ?>" class="link-button-plain">Cancel</a>
            </div>
        </form>
    </div>

    <aside class="commit-aside">
        <div class="rail-card">
            <p class="rail-card-title">What happens next</p>
            <ol class="commit-steps">
                <li>Your shares are reserved and come out of what others can buy.</li>
                <li>We email you an invoice with the company's banking details and a payment reference.</li>
                <li>You pay, quoting that reference.</li>
                <li>Once payment clears, an administrator settles it and the shares go onto the register in your name.</li>
            </ol>
            <p class="rail-fineprint muted">
                If the commitment expires before payment, the shares go back into the available
                pool. Nothing is owed &mdash; a commitment that lapses simply lapses.
            </p>
        </div>
    </aside>
</div>

<script src="/assets/js/commit.js" defer></script>
