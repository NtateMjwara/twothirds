<?php
/**
 * Fees.
 *
 * Three charges, laid out in the order an investor meets them: once on the way
 * in, then twice against profit each period. The worked example is the point of
 * the page — a percentage on its own tells you almost nothing about what lands
 * in your account.
 */
$fees = [
    [
        'rate'  => '6%',
        'name'  => 'Transaction fee',
        'when'  => 'Once, when you buy',
        'base'  => 'of the amount you invest',
        'blurb' => 'Charged upfront on the way in. It covers sourcing and inspecting the '
                 . 'asset, incorporating the company, and the registration and transfer work '
                 . 'that puts your name on the share register. You pay it once, on the shares '
                 . 'you buy, and never again on those shares.',
        'icon'  => 'ti-login-2',
    ],
    [
        'rate'  => '25%',
        'name'  => 'Performance fee',
        'when'  => 'Each period, from profit',
        'base'  => 'of net operating profit',
        'blurb' => 'Our asset management fee. It is charged on what the asset actually earns '
                 . 'after running costs, not on what it is worth, so a vehicle that sits idle '
                 . 'earns us nothing. Managing the operator relationship, chasing collections '
                 . 'and keeping the company compliant is what this pays for.',
        'icon'  => 'ti-chart-line',
    ],
    [
        'rate'  => '15%',
        'name'  => 'Asset Replacement Fund',
        'when'  => 'Each period, from profit',
        'base'  => 'of net operating profit',
        'blurb' => 'Set aside every period so the asset can be replaced at the end of its '
                 . 'working life rather than sold off at whatever it happens to fetch. This '
                 . 'one is not paid to TwoThirds — it stays inside the company, on its balance '
                 . 'sheet, and it is still yours in proportion to your shares.',
        'icon'  => 'ti-shield-bolt',
        'note'  => 'Retained by the company, not a payment to us',
    ],
];
?>
<section class="discovery-masthead">
    <p class="kicker-sm">Our fees</p>
    <h1>Three charges, and nothing else</h1>
    <p class="muted discovery-lede">
        One when you buy, and two taken from what the asset earns. There are no
        monthly platform fees, no admin charges, and no exit fee.
    </p>
</section>

<section class="rail-section" aria-labelledby="fee-heading">
    <div class="section-head">
        <h2 id="fee-heading">What we charge</h2>
    </div>

    <div class="fee-grid">
        <?php foreach ($fees as $fee): ?>
            <article class="fee-card">
                <span class="fee-icon"><i class="ti <?= e($fee['icon']) ?>" aria-hidden="true"></i></span>
                <p class="fee-rate"><?= e($fee['rate']) ?></p>
                <h3 class="fee-name"><?= e($fee['name']) ?></h3>
                <p class="fee-base muted"><?= e($fee['base']) ?></p>
                <p class="fee-when"><i class="ti ti-clock" aria-hidden="true"></i> <?= e($fee['when']) ?></p>
                <p class="fee-blurb muted"><?= e($fee['blurb']) ?></p>
                <?php if (!empty($fee['note'])): ?>
                    <p class="fee-note"><i class="ti ti-info-circle" aria-hidden="true"></i> <?= e($fee['note']) ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="rail-section" aria-labelledby="split-heading">
    <div class="section-head">
        <h2 id="split-heading">How a period's profit is split</h2>
    </div>

    <p class="muted fee-lede">
        Both ongoing fees come off the same figure: net operating profit, which is what the
        asset earned in the period after fuel, maintenance, insurance and the operator's costs.
        If the asset makes nothing, both are zero.
    </p>

    <div class="split-bar" role="img"
         aria-label="Net operating profit is split 60 percent to shareholders, 25 percent performance fee, 15 percent asset replacement fund">
        <span class="split-seg split-shareholders" style="width:60%;"><span class="split-seg-label">60% Shareholders</span></span>
        <span class="split-seg split-performance" style="width:25%;"><span class="split-seg-label">25% Performance</span></span>
        <span class="split-seg split-arf" style="width:15%;"><span class="split-seg-label">15% ARF</span></span>
    </div>

    <ul class="split-key">
        <li><span class="key-dot key-shareholders"></span> <strong>60%</strong> distributable to shareholders</li>
        <li><span class="key-dot key-performance"></span> <strong>25%</strong> performance fee to TwoThirds</li>
        <li><span class="key-dot key-arf"></span> <strong>15%</strong> retained in the company's replacement fund</li>
    </ul>

    <p class="muted fee-footnote">
        Counting the replacement fund, 75% of what the asset earns stays with the company
        and its shareholders. The 15% simply isn't paid out yet — it's buying the next asset.
    </p>
</section>

<section class="rail-section" aria-labelledby="example-heading">
    <div class="section-head">
        <h2 id="example-heading">A worked example</h2>
    </div>

    <p class="muted fee-lede">
        Round numbers, chosen to be easy to follow rather than typical. Real figures depend
        entirely on the asset, the activity and the period.
    </p>

    <div class="worked-example">
        <div class="example-block">
            <h3><span class="example-step">1</span> Buying in</h3>
            <table class="fee-table">
                <tbody>
                    <tr><th scope="row">You invest</th><td>R10&nbsp;000.00</td></tr>
                    <tr><th scope="row">Transaction fee (6%)</th><td class="is-out">&minus;&nbsp;R600.00</td></tr>
                    <tr class="is-total"><th scope="row">Buys shares worth</th><td>R9&nbsp;400.00</td></tr>
                </tbody>
            </table>
            <p class="muted example-note">
                At a NAV of R100.00 a share, that's 94 shares on the register.
            </p>
        </div>

        <div class="example-block">
            <h3><span class="example-step">2</span> A trading period</h3>
            <table class="fee-table">
                <tbody>
                    <tr><th scope="row">Gross revenue</th><td>R180&nbsp;000.00</td></tr>
                    <tr><th scope="row">Operating costs</th><td class="is-out">&minus;&nbsp;R80&nbsp;000.00</td></tr>
                    <tr class="is-subtotal"><th scope="row">Net operating profit</th><td>R100&nbsp;000.00</td></tr>
                    <tr><th scope="row">Performance fee (25%)</th><td class="is-out">&minus;&nbsp;R25&nbsp;000.00</td></tr>
                    <tr><th scope="row">Replacement fund (15%)</th><td class="is-held">&minus;&nbsp;R15&nbsp;000.00</td></tr>
                    <tr class="is-total"><th scope="row">Distributable</th><td>R60&nbsp;000.00</td></tr>
                </tbody>
            </table>
            <p class="muted example-note">
                On 5&nbsp;000 shares issued, that's R12.00 a share &mdash; R1&nbsp;128.00 on a
                94-share holding.
            </p>
        </div>
    </div>
</section>

<section class="rail-section" aria-labelledby="qa-heading">
    <div class="section-head">
        <h2 id="qa-heading">The questions people actually ask</h2>
    </div>

    <div class="fee-qa">
        <details>
            <summary>What if the asset doesn't make a profit?</summary>
            <p>
                Both ongoing charges are a share of net operating profit, so if there isn't
                any, neither is charged. Nothing accrues and nothing is owed later. A loss is
                a loss the company carries — we don't invoice for it.
            </p>
        </details>
        <details>
            <summary>Is there a fee to sell or exit?</summary>
            <p>
                No exit fee. What limits an exit is finding a buyer for the shares, not a
                charge from us.
            </p>
        </details>
        <details>
            <summary>Why is the replacement fund described as a fee at all?</summary>
            <p>
                Because it comes off profit before anything is distributed, so it reduces what
                you receive in a given period exactly as a fee would. The difference is where it
                goes: a performance fee leaves the company, the replacement fund stays on its
                balance sheet and buys the next asset. We list it here rather than burying it
                because seeing 15% disappear from a distribution without explanation is worse
                than reading about it upfront.
            </p>
        </details>
        <details>
            <summary>Are the operator's costs a fee?</summary>
            <p>
                No. Fuel, maintenance, insurance and the operator's own share are operating
                costs, deducted before net operating profit is struck. Every company publishes
                them by period, so you can see exactly what the asset cost to run.
            </p>
        </details>
        <details>
            <summary>Can the rates change?</summary>
            <p>
                The rates that apply to a company are the ones disclosed in its documents at
                the time you buy. Any change would apply to new offerings, not retroactively
                to shares you already hold.
            </p>
        </details>
    </div>
</section>

<section class="rail-section">
    <div class="fee-cta">
        <h2>See what the fees apply to</h2>
        <p class="muted">
            Every live offering publishes its asset, its operator and every trading period it
            has filed.
        </p>
        <p><a href="/discover" class="btn"><i class="ti ti-search" aria-hidden="true"></i> Browse live offerings</a></p>
    </div>
</section>
