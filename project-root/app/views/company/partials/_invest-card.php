<?php
/**
 * The invest card.
 *
 * Expects: $company, $offer, $fundingTarget, $fundedValue, $fundedPct,
 *          $sharesAvailable, $ttm, $isWatching, $primaryActivity.
 *
 * EasyProperties puts a projected IRR at the top of this card. We don't, and
 * won't: a projection is a number someone made up, and every figure on this
 * platform is supposed to be traceable to a filed record. Where there's trading
 * history the card shows the yield the asset has actually produced; where there
 * isn't, it says so instead of substituting an estimate.
 */
$hasHistory = !empty($ttm['profit']) && $fundingTarget > 0;
$ttmYield = $hasHistory ? ((float) $ttm['profit'] / $fundingTarget) * 100 : null;
?>
<aside class="invest-card">
    <div class="invest-target">
        <span class="invest-target-label">Funding target</span>
        <span class="invest-target-value">R<?= number_format($fundingTarget, 0) ?></span>
    </div>

    <p class="invest-funded <?= $fundedPct >= 100 ? 'is-complete' : '' ?>">
        <?= (int) $fundedPct ?>% funded
        <span class="invest-funded-detail muted">R<?= number_format($fundedValue, 0) ?> committed</span>
    </p>

    <div class="funded-bar funded-bar-lg" role="progressbar"
         aria-valuenow="<?= (int) $fundedPct ?>" aria-valuemin="0" aria-valuemax="100"
         aria-label="Percentage of shares subscribed">
        <div class="funded-bar-fill" style="width:<?= (int) $fundedPct ?>%;"></div>
    </div>

    <dl class="invest-figures">
        <div>
            <dt>NAV per share</dt>
            <dd>R<?= number_format((float) $company['nav_per_share'], 2) ?></dd>
        </div>
        <div>
            <dt>Shares available</dt>
            <dd><?= number_format($sharesAvailable) ?> <span class="muted">of <?= number_format((int) $company['shares_issued']) ?></span></dd>
        </div>
        <div>
            <dt>
                Trailing 12-month yield
                <span class="info-tip" tabindex="0" role="note"
                      aria-label="Net operating income over the last twelve months, divided by the company's total share value. Historic, not a forecast.">
                    <i class="ti ti-info-circle" aria-hidden="true"></i>
                </span>
            </dt>
            <dd>
                <?php if ($ttmYield !== null): ?>
                    <?= number_format($ttmYield, 2) ?>%
                <?php else: ?>
                    <span class="muted">No filed periods yet</span>
                <?php endif; ?>
            </dd>
        </div>
        <?php if ($primaryActivity && $primaryActivity['utilisation_rate'] !== null && $primaryActivity['utilisation_rate'] !== ''): ?>
        <div>
            <dt>Utilisation</dt>
            <dd><?= number_format((float) $primaryActivity['utilisation_rate'], 0) ?>%</dd>
        </div>
        <?php endif; ?>
    </dl>

    <?php if ($offer['countdown']): ?>
        <p class="invest-countdown tone-<?= e($offer['tone']) ?>">
            <i class="ti ti-clock" aria-hidden="true"></i> <?= e($offer['countdown']) ?>
        </p>
    <?php endif; ?>

    <?php if ($offer['can_commit']): ?>
        <a href="/commit/<?= e($company['reference']) ?>" class="btn invest-btn">
            <i class="ti ti-circle-plus" aria-hidden="true"></i> Commit to invest
        </a>
    <?php else: ?>
        <button type="button" class="btn invest-btn" disabled><?= e($offer['label']) ?></button>
        <p class="invest-blocked muted">
            <?php if ($offer['status'] === 'scheduled'): ?>
                Shares can't be committed to until the offer opens. Save it to your
                watchlist and you'll have it to hand.
            <?php elseif ($offer['status'] === 'subscribed'): ?>
                Every share has been taken up. Nothing is available until an existing
                holder sells.
            <?php else: ?>
                This offer has closed.
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($_SESSION['user_id'])): ?>
        <form method="post" action="/watchlist/<?= e($company['reference']) ?>/toggle" class="invest-watch">
            <?= csrf_field() ?>
            <input type="hidden" name="return_to" value="<?= e(company_url($company)) ?>">
            <button type="submit" class="btn-outline invest-watch-btn">
                <i class="ti ti-<?= $isWatching ? 'bookmark-filled' : 'bookmark' ?>" aria-hidden="true"></i>
                <?= $isWatching ? 'Saved to watchlist' : 'Save to watchlist' ?>
            </button>
        </form>
    <?php else: ?>
        <p class="invest-watch"><a href="/login" class="btn-outline invest-watch-btn">
            <i class="ti ti-bookmark" aria-hidden="true"></i> Log in to save
        </a></p>
    <?php endif; ?>
</aside>
