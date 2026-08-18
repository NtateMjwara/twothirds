<?php
/**
 * How it works.
 *
 * Written for someone who has never bought a share of anything. Every term is
 * defined before it is used, every percentage is worked through with real
 * numbers, and the risks get their own section rather than a line of small
 * print at the bottom.
 *
 * The entry-price figures come from live listings, not an illustration.
 */
$minPrice = $entry['min'];
$maxPrice = $entry['max'];
$feePct = $feeRate * 100;

// A concrete worked example built around whatever the cheapest live share
// actually is, so the arithmetic on the page matches the arithmetic an investor
// would do today. Falls back to a round number when nothing is open.
$examplePrice = $minPrice !== null && $minPrice > 0 ? $minPrice : 100.00;
$exampleShares = 10;
$exampleSubtotal = $examplePrice * $exampleShares;
$exampleFee = $exampleSubtotal * $feeRate;
$exampleTotal = $exampleSubtotal + $exampleFee;

$contents = [
    'idea'      => 'The idea in one paragraph',
    'terms'     => "The four terms you'll see everywhere",
    'shares'    => 'How shares work',
    'start'     => 'How much you need to start',
    'steps'     => 'From browsing to owning',
    'money'     => 'Where the money goes',
    'risks'     => 'What could go wrong',
    'questions' => 'Common questions',
];
?>
<section class="discovery-masthead">
    <p class="kicker-sm">How it works</p>
    <h1>One company, one asset, one job</h1>
    <p class="muted discovery-lede">
        You don't need to know anything about investing to read this page. It explains
        exactly what you'd be buying, what it costs, what you'd earn, and what could
        go wrong &mdash; in that order.
    </p>
</section>

<div class="guide-layout">
    <nav class="guide-toc" aria-label="On this page">
        <p class="guide-toc-title">On this page</p>
        <ol>
            <?php foreach ($contents as $anchor => $label): ?>
                <li><a href="#<?= e($anchor) ?>"><?= e($label) ?></a></li>
            <?php endforeach; ?>
        </ol>
        <p class="guide-toc-foot muted">
            About a six minute read.
        </p>
    </nav>

    <div class="guide-body">

    <!-- ============================================================
         The idea
         ============================================================ -->
    <section id="idea" class="guide-section">
        <h2>The idea in one paragraph</h2>
        <p class="guide-lead">
            A working vehicle earns money. A minibus on a taxi route, a truck moving
            freight, a bakkie on delivery runs. Normally you'd have to buy the whole
            thing yourself to earn from it. Here, a company is created that owns exactly
            one of those vehicles, and that company is divided into shares. You buy some
            of the shares. The vehicle goes to work, and you get your slice of what it
            earns after costs.
        </p>

        <div class="flow-diagram" role="img"
             aria-label="Investors buy shares in a company; the company owns one vehicle; the vehicle earns income; the income is split back to the shareholders.">
            <div class="flow-step">
                <span class="flow-icon"><i class="ti ti-users" aria-hidden="true"></i></span>
                <p class="flow-label">You and other investors</p>
                <p class="flow-note muted">buy shares</p>
            </div>
            <span class="flow-arrow" aria-hidden="true"><i class="ti ti-arrow-right"></i></span>
            <div class="flow-step">
                <span class="flow-icon"><i class="ti ti-building" aria-hidden="true"></i></span>
                <p class="flow-label">A company</p>
                <p class="flow-note muted">registered, with its own name and number</p>
            </div>
            <span class="flow-arrow" aria-hidden="true"><i class="ti ti-arrow-right"></i></span>
            <div class="flow-step">
                <span class="flow-icon"><i class="ti ti-car" aria-hidden="true"></i></span>
                <p class="flow-label">Owns one vehicle</p>
                <p class="flow-note muted">a specific unit, with a VIN</p>
            </div>
            <span class="flow-arrow" aria-hidden="true"><i class="ti ti-arrow-right"></i></span>
            <div class="flow-step">
                <span class="flow-icon"><i class="ti ti-coins" aria-hidden="true"></i></span>
                <p class="flow-label">That vehicle earns</p>
                <p class="flow-note muted">and the profit comes back to shareholders</p>
            </div>
        </div>

        <div class="callout callout-plain">
            <i class="ti ti-info-circle" aria-hidden="true"></i>
            <p>
                <strong>Why one asset per company?</strong> So you always know exactly what
                you own. If a company owned twenty vehicles, a bad one could hide behind the
                good ones. Here, the thing you bought into is a single vehicle you can look
                up, with its own photographs, valuation and trading history.
            </p>
        </div>
    </section>

    <!-- ============================================================
         Terms
         ============================================================ -->
    <section id="terms" class="guide-section">
        <h2>The four terms you'll see everywhere</h2>
        <p class="muted">
            These four turn up on every listing. Learn them here and the rest of the
            platform reads easily.
        </p>

        <div class="term-grid">
            <article class="term-card">
                <span class="term-icon"><i class="ti ti-certificate" aria-hidden="true"></i></span>
                <h3>Shares</h3>
                <p>
                    Your stake in the company that owns the asset. Held in that company's
                    share register under your name &mdash; not in a fund, not pooled with
                    anyone else's.
                </p>
                <p class="term-plain"><strong>In plain terms:</strong> your slice of the pie.</p>
            </article>

            <article class="term-card">
                <span class="term-icon"><i class="ti ti-calculator" aria-hidden="true"></i></span>
                <h3>NAV per share</h3>
                <p>
                    Net asset value divided by the number of shares issued. It's what one
                    share is worth, and it's what you pay for one.
                </p>
                <p class="term-plain"><strong>In plain terms:</strong> the price of one slice.</p>
            </article>

            <article class="term-card">
                <span class="term-icon"><i class="ti ti-activity-heartbeat" aria-hidden="true"></i></span>
                <h3>Utilisation</h3>
                <p>
                    The share of available working time the asset actually spent earning in
                    its last reported period. A vehicle parked for half the month has 50%
                    utilisation.
                </p>
                <p class="term-plain"><strong>In plain terms:</strong> how busy it's been.</p>
            </article>

            <article class="term-card">
                <span class="term-icon"><i class="ti ti-shield-bolt" aria-hidden="true"></i></span>
                <h3>Asset replacement fund</h3>
                <p>
                    Money set aside from profit each period so the vehicle can be replaced
                    when it wears out, instead of the company being left with nothing.
                    Shortened to ARF on some pages.
                </p>
                <p class="term-plain"><strong>In plain terms:</strong> the savings pot for the next one.</p>
            </article>
        </div>
    </section>

    <!-- ============================================================
         How shares work
         ============================================================ -->
    <section id="shares" class="guide-section">
        <h2>How shares work</h2>

        <h3>Where the price comes from</h3>
        <p>
            The price of a share isn't chosen. It's the value of the asset divided by
            the number of shares the company issued:
        </p>

        <div class="formula">
            <span class="formula-part">Asset value</span>
            <span class="formula-op" aria-hidden="true">&divide;</span>
            <span class="formula-part">Shares issued</span>
            <span class="formula-op" aria-hidden="true">=</span>
            <span class="formula-part is-result">NAV per share</span>
        </div>

        <p>
            So a R400,000 minibus divided into 4,000 shares gives a NAV of
            <strong>R100.00</strong> a share. Divide the same minibus into 400 shares
            instead and each one costs R1,000. Same vehicle, same total value &mdash;
            just sliced differently.
        </p>

        <div class="callout callout-plain">
            <i class="ti ti-scale" aria-hidden="true"></i>
            <p>
                <strong>More shares doesn't mean more company.</strong> The number of shares
                is fixed when the company is created. New shares aren't issued later to
                raise more money, so your slice can't shrink because someone else bought in
                after you.
            </p>
        </div>

        <h3>What you own, and what you don't</h3>
        <div class="two-col-list">
            <div class="do-own">
                <p class="col-title"><i class="ti ti-check" aria-hidden="true"></i> You do own</p>
                <ul>
                    <li>A recorded stake in a registered company, in your name</li>
                    <li>Your share of that company's profit when it's distributed</li>
                    <li>Your share of the asset's value, including anything in the replacement fund</li>
                    <li>The right to see the company's filings &mdash; they're public on its page</li>
                </ul>
            </div>
            <div class="dont-own">
                <p class="col-title"><i class="ti ti-x" aria-hidden="true"></i> You don't own</p>
                <ul>
                    <li>The vehicle itself &mdash; the company owns it, and you own part of the company</li>
                    <li>Any right to drive or use it</li>
                    <li>A loan or a guaranteed return. This is ownership, not lending</li>
                    <li>A promise of any particular income. It depends on what the asset earns</li>
                </ul>
            </div>
        </div>

        <h3>What makes the price move</h3>
        <p>
            NAV per share changes when the value of what the company holds changes.
            Three things do that:
        </p>
        <ul class="reason-list">
            <li>
                <strong>Revaluation.</strong> Vehicles lose value with age and mileage. When
                the asset is revalued, NAV moves with it &mdash; usually down, over time.
            </li>
            <li>
                <strong>The replacement fund growing.</strong> Money retained in the company
                is still the company's, so it adds to what a share is worth.
            </li>
            <li>
                <strong>Damage or a major repair.</strong> Anything that changes what the
                asset is worth changes what a share in it is worth.
            </li>
        </ul>
        <p class="muted">
            Every share moves together. If NAV changes, it changes for everyone holding
            shares in that company, on the same day, by the same proportion.
        </p>
    </section>

    <!-- ============================================================
         How much to start
         ============================================================ -->
    <section id="start" class="guide-section">
        <h2>How much you need to start</h2>

        <?php if ($minPrice !== null): ?>
            <div class="price-highlight">
                <p class="price-highlight-label">Cheapest share available right now</p>
                <p class="price-highlight-value">R<?= number_format($minPrice, 2) ?></p>
                <p class="price-highlight-note muted">
                    Across <?= number_format($entry['count']) ?> open offering<?= $entry['count'] === 1 ? '' : 's' ?>,
                    share prices run from R<?= number_format($minPrice, 2) ?>
                    to R<?= number_format($maxPrice, 2) ?>. This figure is live, not an example.
                </p>
            </div>
        <?php else: ?>
            <div class="price-highlight is-empty">
                <p class="price-highlight-label">Nothing open right now</p>
                <p class="price-highlight-note muted">
                    There are no offerings with shares available at the moment, so there's no
                    live entry price to quote. The worked example below uses a round R100.00 a
                    share.
                </p>
            </div>
        <?php endif; ?>

        <p>
            <strong>There's no minimum investment set by us.</strong> The smallest amount you
            can put in is the price of one share in whichever company you choose, plus the
            transaction fee. You buy whole shares &mdash; there are no fractions &mdash; so
            the cost goes up in steps of one share price.
        </p>

        <h3>What ten shares would actually cost</h3>
        <table class="fee-table start-table">
            <tbody>
                <tr>
                    <th scope="row"><?= $exampleShares ?> shares at R<?= number_format($examplePrice, 2) ?></th>
                    <td>R<?= number_format($exampleSubtotal, 2) ?></td>
                </tr>
                <tr>
                    <th scope="row">Transaction fee (<?= rtrim(rtrim(number_format($feePct, 1), '0'), '.') ?>%)</th>
                    <td class="is-out">+&nbsp;R<?= number_format($exampleFee, 2) ?></td>
                </tr>
                <tr class="is-total">
                    <th scope="row">Total you'd pay</th>
                    <td>R<?= number_format($exampleTotal, 2) ?></td>
                </tr>
            </tbody>
        </table>
        <p class="muted small-note">
            The transaction fee is charged once, on the way in, and never again on those
            shares. It's the only fee you pay directly &mdash; the other two come out of
            profit before it's distributed. <a href="/fees">See all three fees</a>.
        </p>

        <div class="callout callout-plain">
            <i class="ti ti-wallet" aria-hidden="true"></i>
            <p>
                <strong>Start small, add later.</strong> Nothing stops you buying one share
                to begin with and more of the same company afterwards, or spreading the same
                money across several companies in different industries. Each purchase is
                priced at the NAV on the day you commit.
            </p>
        </div>
    </section>

    <!-- ============================================================
         Steps
         ============================================================ -->
    <section id="steps" class="guide-section">
        <h2>From browsing to owning</h2>

        <ol class="how-steps">
            <li>
                <span class="how-step-index">01</span>
                <div>
                    <h3>Find an offering</h3>
                    <p class="muted">
                        Filter by industry, activity, asset class or operating area. Every card
                        shows NAV per share, how many shares are still available, and how
                        subscribed the company already is. Save anything you like the look of
                        to your watchlist &mdash; you're not committing to anything by doing that.
                    </p>
                </div>
            </li>
            <li>
                <span class="how-step-index">02</span>
                <div>
                    <h3>Read the company, not the pitch</h3>
                    <p class="muted">
                        Each listing carries its registration details, its directors, the
                        asset's valuation and roadworthy status, and every trading period it
                        has filed. Documents are attached to the record. A company with no
                        trading history isn't necessarily bad &mdash; but it does mean there's
                        no earnings record to judge it on, and the page says so.
                    </p>
                </div>
            </li>
            <li>
                <span class="how-step-index">03</span>
                <div>
                    <h3>Commit</h3>
                    <p class="muted">
                        A commitment reserves a number of shares at the current NAV and expires
                        if it isn't settled. Reserved shares come out of the available count
                        straight away, so the number on a card is genuinely what's left. No
                        payment is taken online &mdash; settlement is arranged with you directly.
                    </p>
                </div>
            </li>
            <li>
                <span class="how-step-index">04</span>
                <div>
                    <h3>Settlement puts you on the register</h3>
                    <p class="muted">
                        Once payment is confirmed, an administrator settles the commitment and
                        your shares are written to the company's share register. It's an
                        append-only ledger, so corrections are new entries and the history stays
                        intact. From that point the holding shows in your portfolio.
                    </p>
                </div>
            </li>
        </ol>
    </section>

    <!-- ============================================================
         Money flow
         ============================================================ -->
    <section id="money" class="guide-section">
        <h2>Where the money goes</h2>
        <p>
            The asset earns. Running costs come off first &mdash; fuel, maintenance,
            insurance, the operator's own share. What's left is called
            <strong>net operating profit</strong>, and that's the figure everything else
            is a percentage of.
        </p>

        <div class="split-bar" role="img"
             aria-label="Net operating profit is split 60 percent to shareholders, 25 percent performance fee, 15 percent to the asset replacement fund">
            <span class="split-seg split-shareholders" style="width:60%;"><span class="split-seg-label">60% Shareholders</span></span>
            <span class="split-seg split-performance" style="width:25%;"><span class="split-seg-label">25% Performance</span></span>
            <span class="split-seg split-arf" style="width:15%;"><span class="split-seg-label">15% ARF</span></span>
        </div>

        <ul class="split-key">
            <li><span class="key-dot key-shareholders"></span> <strong>60%</strong> distributable to shareholders &mdash; your share of this is proportional to how many shares you hold</li>
            <li><span class="key-dot key-performance"></span> <strong>25%</strong> performance fee to TwoThirds for managing the asset</li>
            <li><span class="key-dot key-arf"></span> <strong>15%</strong> retained in the company's replacement fund &mdash; still yours, just not paid out yet</li>
        </ul>

        <p class="muted small-note">
            If the asset makes no profit in a period, none of these are charged. There's
            nothing to take a percentage of, and nothing accrues to be collected later.
            <a href="/fees">Full detail on the fees page</a>.
        </p>
    </section>

    <!-- ============================================================
         Risks
         ============================================================ -->
    <section id="risks" class="guide-section">
        <h2>What could go wrong</h2>
        <p class="guide-lead">
            This is a real business owning a real vehicle, not a savings account. It can
            lose money, and you can get back less than you put in. These are the specific
            ways that happens.
        </p>

        <div class="risk-grid">
            <article class="risk-card">
                <h3><i class="ti ti-parking-off" aria-hidden="true"></i> The asset stops earning</h3>
                <p>
                    A breakdown, an accident, an impounded vehicle or a driver who leaves. A
                    parked asset earns nothing, and costs keep running. Utilisation on the
                    listing tells you how consistently it has been working so far.
                </p>
            </article>

            <article class="risk-card">
                <h3><i class="ti ti-trending-down" aria-hidden="true"></i> The asset loses value</h3>
                <p>
                    Vehicles depreciate. As the asset is revalued downwards over its life, NAV
                    per share falls with it. Income is meant to make up for that over time,
                    but it isn't guaranteed to.
                </p>
            </article>

            <article class="risk-card">
                <h3><i class="ti ti-user-x" aria-hidden="true"></i> The operator fails</h3>
                <p>
                    The company depends on whoever runs the asset day to day. If they stop
                    performing, income stops until a replacement is found, and finding one
                    takes time.
                </p>
            </article>

            <article class="risk-card">
                <h3><i class="ti ti-lock-open-off" aria-hidden="true"></i> You can't sell on demand</h3>
                <p>
                    This is the one people underestimate. There's no market to sell your
                    shares into, so getting your money out depends on finding a buyer.
                    <strong>Treat anything you put in as money you won't need back at a
                    particular time.</strong>
                </p>
            </article>

            <article class="risk-card">
                <h3><i class="ti ti-report-off" aria-hidden="true"></i> No income is promised</h3>
                <p>
                    Distributions come from profit. A period with no profit means no
                    distribution &mdash; not a smaller one, and not one deferred to next time.
                    Past periods say what the asset has earned; they don't promise what it will.
                </p>
            </article>

            <article class="risk-card">
                <h3><i class="ti ti-hourglass-empty" aria-hidden="true"></i> The offer may not fill</h3>
                <p>
                    An offering that doesn't reach full subscription may not proceed as
                    planned. The company page shows how subscribed it is before you commit.
                </p>
            </article>
        </div>

        <div class="callout callout-warn">
            <i class="ti ti-alert-triangle" aria-hidden="true"></i>
            <p>
                Nothing on this platform is advice about whether an investment suits you. If
                you're not sure, speak to someone qualified to look at your own circumstances
                before committing money.
            </p>
        </div>
    </section>

    <!-- ============================================================
         FAQ
         ============================================================ -->
    <section id="questions" class="guide-section">
        <h2>Common questions</h2>

        <div class="fee-qa">
            <details>
                <summary>Am I buying a car?</summary>
                <p>
                    No. You're buying shares in a company, and that company owns the vehicle.
                    It matters because it's what limits your exposure: the company owns the
                    asset and carries its obligations, not you personally.
                </p>
            </details>
            <details>
                <summary>Can I use or drive the vehicle?</summary>
                <p>
                    No. It's out working, which is the entire point &mdash; a vehicle being
                    used by its owners isn't earning. What you get is a share of what it makes.
                </p>
            </details>
            <details>
                <summary>Is this a loan? Do I get interest?</summary>
                <p>
                    Neither. A loan pays a fixed rate back regardless of how the business does.
                    Here you own part of the company, so you share in what it earns &mdash;
                    more when it does well, nothing when it doesn't.
                </p>
            </details>
            <details>
                <summary>How and when do I get paid?</summary>
                <p>
                    Distributions follow filed trading periods, and only when there's profit to
                    distribute. Each company publishes its periods on its own page, so you can
                    see the pattern before you buy rather than after.
                </p>
            </details>
            <details>
                <summary>How do I get my money out?</summary>
                <p>
                    By selling your shares to someone else &mdash; and there's no resale market
                    for that yet, so it depends on finding a buyer directly. There's no exit
                    fee, but there's also no exit button. This is the most important limitation
                    on the platform and it's why the risks section says to treat this as money
                    you won't need back at a particular time.
                </p>
            </details>
            <details>
                <summary>What happens when the vehicle reaches the end of its life?</summary>
                <p>
                    That's what the replacement fund is for. Money set aside over the asset's
                    working life pays for the next one, so the company continues rather than
                    being wound up and the asset sold for whatever it happens to fetch.
                </p>
            </details>
            <details>
                <summary>What about tax?</summary>
                <p>
                    We don't give tax advice and can't tell you how any of this will be treated
                    in your hands &mdash; it depends on your own circumstances. Speak to a tax
                    practitioner, and keep your records from your portfolio page.
                </p>
            </details>
            <details>
                <summary>What can I actually check before I commit?</summary>
                <p>
                    For every listing: the company's registration details and directors, the
                    asset's VIN, valuation, valuation date, mileage, insurance and roadworthy
                    status, the operator and operating area, every trading period filed, and
                    the attached documents. If something you'd want to see isn't there, that
                    absence is itself information.
                </p>
            </details>
        </div>
    </section>

    <!-- ============================================================
         Sectors + CTA
         ============================================================ -->
    <section class="guide-section">
        <h2>Where assets earn</h2>
        <p class="muted">
            Twelve industries, each with its own commercial activities. The industry an
            asset works in shapes how steady its income is and what can interrupt it.
        </p>

        <div class="chip-rail" role="group" aria-label="Industries">
            <?php foreach ($sectors as $s): ?>
                <a class="rail-chip<?= (int) $s['listing_count'] === 0 ? ' is-empty' : '' ?>"
                   href="/browse/<?= e($s['slug']) ?>" title="<?= e($s['tagline'] ?? '') ?>">
                    <span class="rail-chip-icon"><i class="ti <?= e($s['icon']) ?>" aria-hidden="true"></i></span>
                    <span class="rail-chip-label"><?= e($s['name']) ?></span>
                    <span class="rail-chip-count"><?= number_format((int) $s['listing_count']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="fee-cta">
            <h2>Ready to look at what's open?</h2>
            <p class="muted">
                <?= number_format($totalActive) ?> live offering<?= $totalActive === 1 ? '' : 's' ?>.
                Every one publishes its asset, its operator and every trading period it has filed.
            </p>
            <p>
                <a href="/browse" class="btn"><i class="ti ti-search" aria-hidden="true"></i> Browse offerings</a>
                <a href="/fees" class="btn-outline">See the fees</a>
            </p>
        </div>
    </section>

    </div>
</div>

<script src="/assets/js/discovery.js" defer></script>
