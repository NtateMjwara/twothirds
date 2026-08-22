<?php
/**
 * Portfolio.
 *
 * Laid out like a broker dashboard: a value banner across the top, a left rail
 * of small standing figures, and the holdings themselves grouped by industry on
 * the right.
 *
 * Two things a broker page would have are deliberately absent. There is no
 * value-over-time chart, because no NAV history is stored and a line drawn from
 * two points would be a decoration pretending to be data. And nothing here says
 * money has been paid out, because distributions aren't recorded yet.
 */
$hasHoldings = $holdings !== [];
$movementKnown = $totals['movement'] !== null;
$isUp = $movementKnown && $totals['movement'] >= 0;
?>
<div class="portfolio-head">
    <h1>Your portfolio</h1>
    <p class="muted">Everything registered in your name, valued at each company's current NAV.</p>
</div>

<!-- ============================================================
     Value banner
     ============================================================ -->
<section class="value-banner<?= $movementKnown ? ($isUp ? ' is-up' : ' is-down') : ' is-neutral' ?>">
    <div class="value-primary">
        <p class="value-figure">R<?= number_format($totals['value'], 2) ?></p>
        <p class="value-label">Current portfolio value</p>
    </div>

    <div class="value-secondary">
        <?php if ($movementKnown): ?>
            <p class="value-figure">
                <?= $totals['movement'] >= 0 ? '+' : '&minus;' ?>R<?= number_format(abs($totals['movement']), 2) ?>
            </p>
            <p class="value-label">
                Movement against what you paid
                <?php if (!$totals['cost_complete']): ?>
                    <span class="value-caveat">on the R<?= number_format($totals['cost_known_value'], 2) ?> we have a purchase price for</span>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p class="value-figure value-figure-muted">&mdash;</p>
            <p class="value-label">No purchase price on record</p>
        <?php endif; ?>
    </div>

    <?php if ($movementKnown): ?>
        <div class="value-pct">
            <span class="value-pct-figure">
                <?= $totals['movement_pct'] >= 0 ? '+' : '' ?><?= number_format($totals['movement_pct'], 2) ?>%
            </span>
            <span class="value-pct-label">Profit / loss</span>
        </div>
    <?php endif; ?>
</section>

<?php if ($hasHoldings && $totals['unknown_cost'] > 0): ?>
    <div class="callout callout-plain portfolio-note">
        <i class="ti ti-info-circle" aria-hidden="true"></i>
        <p>
            <strong><?= (int) $totals['unknown_cost'] ?></strong>
            of your <?= (int) $totals['holdings'] ?> holding<?= $totals['holdings'] === 1 ? '' : 's' ?>
            <?= $totals['unknown_cost'] === 1 ? 'was' : 'were' ?> settled before we started recording
            the price paid, so <?= $totals['unknown_cost'] === 1 ? 'it is' : 'they are' ?> counted in your
            value but not in the movement figure. We'd rather leave that gap visible than fill it with
            a guess.
        </p>
    </div>
<?php endif; ?>

<div class="portfolio-layout">

    <!-- ============================================================
         Left rail
         ============================================================ -->
    <aside class="portfolio-rail">
        <div class="rail-card rail-card-action">
            <div>
                <p class="rail-label">Available to invest in</p>
                <p class="rail-figure"><?= number_format($openOfferings) ?></p>
                <p class="rail-note muted">open offering<?= $openOfferings === 1 ? '' : 's' ?></p>
            </div>
            <a href="<?= e(invest_url(['availability' => 'open'])) ?>" class="rail-cta" aria-label="Browse open offerings">
                <i class="ti ti-plus" aria-hidden="true"></i>
            </a>
        </div>

        <div class="rail-card">
            <p class="rail-card-title">Reported to you</p>
            <div class="rail-split">
                <div>
                    <p class="rail-figure-sm">R<?= number_format($income['total'], 2) ?></p>
                    <p class="rail-note muted">your share of profit</p>
                </div>
                <div>
                    <p class="rail-figure-sm"><?= number_format($income['periods']) ?></p>
                    <p class="rail-note muted">periods filed</p>
                </div>
            </div>
            <p class="rail-fineprint muted">
                Your proportion of net operating profit reported by your companies since you were
                registered. It's what has been <em>earned</em> on your shares &mdash; distributions
                are recorded separately and are not shown here yet.
            </p>
        </div>

        <div class="rail-card">
            <p class="rail-card-title">Position</p>
            <dl class="rail-stats">
                <div>
                    <dt>Holdings</dt>
                    <dd><?= number_format($totals['holdings']) ?></dd>
                </div>
                <div>
                    <dt>Industries</dt>
                    <dd><?= number_format(count($allocation)) ?></dd>
                </div>
                <div>
                    <dt>Pending</dt>
                    <dd><?= number_format(count($pending)) ?></dd>
                </div>
            </dl>
            <?php if ($hasHoldings && count($allocation) === 1): ?>
                <p class="rail-fineprint is-warn">
                    <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                    Everything you hold is in one industry. Whatever affects that industry affects
                    all of it at once.
                </p>
            <?php endif; ?>
        </div>

        <div class="rail-card rail-card-quiet">
            <p class="rail-card-title">Documents</p>
            <p class="rail-fineprint muted">
                Each company publishes its own filings. Open a holding below to see its trading
                periods, valuation and documents.
            </p>
            <a href="/account/watchlist" class="rail-link">
                <i class="ti ti-bookmark" aria-hidden="true"></i> Your watchlist
            </a>
        </div>
    </aside>

    <!-- ============================================================
         Main column
         ============================================================ -->
    <div class="portfolio-main">

        <?php if (!$hasHoldings): ?>
            <div class="empty-state portfolio-empty">
                <div class="asset-icon"><i class="ti ti-chart-pie" aria-hidden="true"></i></div>
                <h2>Nothing on the register yet</h2>
                <p class="muted">
                    Once a commitment settles, the shares appear here with the company's current
                    valuation. Until then this page stays empty &mdash; it only ever shows what
                    is genuinely registered in your name.
                </p>
                <p><a href="<?= e(invest_url()) ?>" class="btn"><i class="ti ti-search" aria-hidden="true"></i> Browse offerings</a></p>
            </div>
        <?php else: ?>

            <!-- Allocation -->
            <section class="portfolio-section">
                <div class="section-head">
                    <h2>Where your money is</h2>
                    <span class="muted section-sub">By industry, at current value</span>
                </div>

                <div class="allocation-bar" role="img"
                     aria-label="<?= e(implode('; ', array_map(
                         static fn ($a) => $a['name'] . ' ' . number_format($a['share'], 1) . '%',
                         $allocation
                     ))) ?>">
                    <?php foreach ($allocation as $i => $group): ?>
                        <span class="allocation-seg alloc-<?= ($i % 6) + 1 ?>"
                              style="width:<?= number_format($group['share'], 4) ?>%;"></span>
                    <?php endforeach; ?>
                </div>

                <ul class="allocation-key">
                    <?php foreach ($allocation as $i => $group): ?>
                        <li>
                            <span class="key-dot alloc-<?= ($i % 6) + 1 ?>"></span>
                            <span class="key-name"><?= e($group['name']) ?></span>
                            <span class="key-value">
                                R<?= number_format($group['value'], 2) ?>
                                <span class="muted"><?= number_format($group['share'], 1) ?>%</span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <!-- Holdings, grouped by industry -->
            <?php
            $bySector = [];
            foreach ($holdings as $holding) {
                $bySector[$holding['sector_name'] ?: 'Unclassified'][] = $holding;
            }
            ?>
            <section class="portfolio-section">
                <div class="section-head">
                    <h2>Your holdings</h2>
                    <span class="muted section-sub"><?= number_format($totals['holdings']) ?> companies</span>
                </div>

                <?php foreach ($bySector as $sectorName => $rows): ?>
                    <div class="holding-group">
                        <p class="holding-group-title">
                            <i class="ti <?= e($rows[0]['sector_icon']) ?>" aria-hidden="true"></i>
                            <?= e($sectorName) ?>
                            <span class="muted"><?= count($rows) ?></span>
                        </p>

                        <?php foreach ($rows as $h): ?>
                            <a href="<?= e(company_url($h)) ?>" class="holding-row">
                                <span class="holding-identity">
                                    <span class="holding-name"><?= e($h['name']) ?></span>
                                    <span class="holding-meta muted">
                                        <span class="ref-badge"><?= e($h['reference']) ?></span>
                                        <?php $vehicle = trim(($h['make'] ?? '') . ' ' . ($h['model'] ?? '')); ?>
                                        <?php if ($vehicle !== ''): ?><?= e($vehicle) ?><?php endif; ?>
                                        <?php if (!empty($h['location'])): ?>&middot; <?= e($h['location']) ?><?php endif; ?>
                                    </span>
                                </span>

                                <span class="holding-figure">
                                    <span class="holding-figure-value"><?= number_format($h['shares']) ?></span>
                                    <span class="holding-figure-label">shares</span>
                                </span>

                                <span class="holding-figure">
                                    <span class="holding-figure-value">R<?= number_format($h['value'], 2) ?></span>
                                    <span class="holding-figure-label">current value</span>
                                </span>

                                <span class="holding-figure holding-movement">
                                    <?php if ($h['movement'] !== null): ?>
                                        <span class="holding-figure-value <?= $h['movement'] >= 0 ? 'is-up' : 'is-down' ?>">
                                            <?= $h['movement'] >= 0 ? '+' : '' ?><?= number_format($h['movement_pct'], 2) ?>%
                                        </span>
                                        <span class="holding-figure-label">
                                            <?= $h['movement'] >= 0 ? '+' : '&minus;' ?>R<?= number_format(abs($h['movement']), 2) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="holding-figure-value is-unknown">&mdash;</span>
                                        <span class="holding-figure-label">cost not recorded</span>
                                    <?php endif; ?>
                                </span>

                                <i class="ti ti-chevron-right holding-chevron" aria-hidden="true"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <!-- Pending commitments -->
        <?php if (!empty($pending)): ?>
        <section class="portfolio-section">
            <div class="section-head">
                <h2>Awaiting settlement</h2>
                <span class="muted section-sub">Reserved, not yet on the register</span>
            </div>

            <p class="muted section-lede">
                These shares are held for you and already removed from what other investors can
                see as available. They become part of your portfolio value once payment is
                confirmed.
            </p>

            <?php foreach ($pending as $p): ?>
                <?php
                $expires = strtotime((string) $p['expires_at']);
                $hoursLeft = ($expires - time()) / 3600;
                ?>
                <div class="pending-row<?= $hoursLeft < 48 ? ' is-urgent' : '' ?>">
                    <span class="holding-identity">
                        <span class="holding-name">
                            <a href="<?= e(company_url(['name' => $p['company_name'], 'reference' => $p['company_reference']])) ?>"><?= e($p['company_name']) ?></a>
                        </span>
                        <span class="holding-meta muted">
                            <span class="ref-badge"><?= e($p['reference']) ?></span>
                        </span>
                    </span>

                    <span class="holding-figure">
                        <span class="holding-figure-value"><?= number_format((int) $p['shares_requested']) ?></span>
                        <span class="holding-figure-label">shares</span>
                    </span>

                    <span class="holding-figure">
                        <span class="holding-figure-value">
                            R<?= number_format($p['shares_requested'] * (float) ($p['nav_at_commitment'] ?? $p['nav_per_share']), 2) ?>
                        </span>
                        <span class="holding-figure-label">
                            <?= isset($p['nav_at_commitment']) && $p['nav_at_commitment'] !== null ? 'agreed price' : 'at current NAV' ?>
                        </span>
                    </span>

                    <span class="holding-figure">
                        <span class="holding-figure-value <?= $hoursLeft < 48 ? 'is-down' : '' ?>">
                            <?= $hoursLeft < 0 ? 'Expired' : ($hoursLeft < 48 ? round($hoursLeft) . 'h' : round($hoursLeft / 24) . 'd') ?>
                        </span>
                        <span class="holding-figure-label">to settle</span>
                    </span>

                    <form method="post" action="/account/commitments/<?= (int) $p['id'] ?>/withdraw"
                          onsubmit="return confirm('Withdraw this commitment? The shares go back into the available pool immediately.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-compact">Withdraw</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>
    </div>
</div>
