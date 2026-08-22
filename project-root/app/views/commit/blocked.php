<?php
/**
 * Why a commitment can't proceed.
 *
 * A 403 or a silent redirect would leave someone guessing. Each reason names
 * the thing standing in the way and, where the investor can act on it, links
 * straight there.
 */
?>
<div class="empty-state" style="max-width:560px; margin:3rem auto;">
    <div class="asset-icon">
        <i class="ti <?= $reason === 'kyc' ? 'ti-shield-check' : 'ti-building-bank' ?>" aria-hidden="true"></i>
    </div>

    <?php if ($reason === 'kyc'): ?>
        <h1 style="font-size:1.4rem;">Verify your identity first</h1>
        <p class="muted">
            <?php if ($status === 'pending'): ?>
                Your documents are with our team. Verification usually takes a working day or
                two, and you'll be notified as soon as it's done &mdash; then you can commit.
            <?php elseif ($status === 'rejected'): ?>
                Your last submission wasn't approved. Correct what was flagged and submit again.
            <?php else: ?>
                Subscribing for shares in a company means we have to know who you are. It's a
                FICA requirement, not a formality, and it takes a few minutes.
            <?php endif; ?>
        </p>
        <p><a href="/account/kyc" class="btn"><i class="ti ti-shield-check" aria-hidden="true"></i> Go to verification</a></p>
    <?php else: ?>
        <h1 style="font-size:1.4rem;">This offering isn't ready to take payment</h1>
        <p class="muted">
            <?= e($company['name']) ?> hasn't had its banking details captured yet, so we can't
            issue you an invoice. This is on us, not you &mdash; it's usually a day or two
            after incorporation.
        </p>
        <p class="muted">Save it to your watchlist and you'll have it to hand.</p>
    <?php endif; ?>

    <p><a href="<?= e(company_url($company)) ?>" class="link-button-plain">Back to <?= e($company['name']) ?></a></p>
</div>
