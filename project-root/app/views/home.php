<?php
/**
 * Homepage.
 *
 * The brand strip is a plain array so it can be edited without touching markup.
 * Drop each logo at public_html/assets/img/brands/<file>. Each renders into a
 * fixed 132x44 slot, so export transparent PNGs at 2x — roughly 264x88 — and
 * let the shorter dimension fall where it falls. Anything larger just costs
 * bandwidth; anything smaller looks soft on a retina screen.
 *
 * A note on what these logos claim. EasyEquities can show a Telkom logo because
 * you are literally buying Telkom shares. Here the relationship is different:
 * the asset earns *on* these platforms, it isn't a stake in them. The copy and
 * the footnote below say so plainly, because a wall of logos with no
 * qualification reads as "these companies endorse us", which isn't true and is
 * the kind of thing that attracts a letter.
 */
$brands = [
    ['name' => 'Uber',        'file' => 'uber.png'],
    ['name' => 'Uber Eats',   'file' => 'ubereats.png'],
    ['name' => 'Mr D',        'file' => 'mr-d.png'],
    ['name' => 'Bolt',        'file' => 'bolt.webp'],
    ['name' => 'Pick n Pay',  'file' => 'picknpay.png'],
    ['name' => 'Woolworths',  'file' => 'woolworths.png'],
    ['name' => 'Checkers Sixty60', 'file' => 'sixty60.jpg'],
    ['name' => 'The Courier Guy', 'file' => 'courier.jpg'],
];

// Routes into the guide. Each href is an anchor on /how-it-works, so a visitor
// lands on the section that answers the question rather than at the top of a
// long page with the answer somewhere below the fold.
$journey = [
    [
        'label' => 'How shares work',
        'blurb' => 'What you own, what sets the price, and what moves it.',
        'href'  => '/how-it-works#shares',
    ],
    [
        'label' => 'How much you need to start',
        'blurb' => 'The real entry price today, with the fee worked in.',
        'href'  => '/how-it-works#start',
    ],
    [
        'label' => 'Where my money goes',
        'blurb' => 'How income is split between you, us, and the replacement fund.',
        'href'  => '/how-it-works#money',
    ],
];

// What an owner-operator carries and a shareholder doesn't.
$handled = [
    ['icon' => 'ti-user-search',      'label' => 'Finding operators'],
    ['icon' => 'ti-license',          'label' => 'Permits and licensing'],
    ['icon' => 'ti-clipboard-check',  'label' => 'Roadworthy and inspections'],
    ['icon' => 'ti-shield-check',     'label' => 'Insurance and claims'],
    ['icon' => 'ti-tools',            'label' => 'Servicing and repairs'],
    ['icon' => 'ti-gas-station',      'label' => 'Fuel and running costs'],
    ['icon' => 'ti-receipt-2',        'label' => 'Collecting the income'],
    ['icon' => 'ti-file-analytics',   'label' => 'Reporting and compliance'],
];
?>
<section class="hero">
    <div class="hero-inner">
        <div class="hero-brand">
            <p class="kicker">TwoThirds</p>
            <p class="kicker-tag">Investment Management</p>
        </div>
        <div class="hero-body">
            <h1>Get Rich Slowly.</h1>
            <p class="hero-lede">
                TwoThirds gives you the tools to build long-term wealth
                by gradually accumulating small pieces of productive assets
                across multiple industries in South Africa.
            </p>
            <p><a href="/discover" class="btn"> Explore Our Latest Offering <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></p>
        </div>
    </div>
</section>

<!-- ============================================================
     Where the assets earn
     ============================================================ -->
<section class="brand-strip-section">
    <h2 class="contrast-heading">
        <span class="struck">Consumption</span>. Ownership.
    </h2>
    <p class="section-lede muted">
        Live in the economy. Own a part of it.
    </p>

    <div class="brand-strip-wrap">
        <button class="strip-arrow strip-prev" type="button"
                aria-label="Previous logos" aria-controls="brandStrip">
            <i class="ti ti-chevron-left" aria-hidden="true"></i>
        </button>

        <ul class="brand-strip" id="brandStrip" tabindex="0">
            <?php foreach ($brands as $brand): ?>
                <li class="brand-logo">
                    <img src="/assets/img/brands/<?= e($brand['file']) ?>"
                         alt="<?= e($brand['name']) ?>" width="132" height="44">
                </li>
            <?php endforeach; ?>
        </ul>

        <button class="strip-arrow strip-next" type="button"
                aria-label="Next logos" aria-controls="brandStrip">
            <i class="ti ti-chevron-right" aria-hidden="true"></i>
        </button>
    </div>

    <p class="brand-strip-cta">
        <a href="/discover" class="btn">Browse live offerings</a>
    </p>
    <p class="brand-strip-note muted">
        These logos show where the assets operate. TwoThirds is independent and
        isn't affiliated with, endorsed by, or investing in these companies.
    </p>
</section>

<!-- ============================================================
     Owner-operator vs shareholder
     ============================================================ -->
<section class="contrast-section">
    <h2 class="contrast-heading">
        <!--<span class="struck">Consumption</span>--> Asset Management
    </h2>
    <p class="section-lede muted">
        TwoThirds appoints independent fleet management companies to manage 
        the operational activities of its vehicle portfolios.
    </p>

    <div class="contrast-layout">
        <div class="contrast-intro">
            <p>
                Fleet managers, in turn, are responsible for the 
                day-to-day management and performance of the fleets 
                entrusted to them, while TwoThirds monitors their 
                performance, compliance and adherence to their 
                management mandates.
            </p>
            <p class="contrast-punch">Less admin. More ownership.</p>
            <p><a href="/fees" class="btn btn-ink">Our Fees</a></p>
        </div>

        <ul class="handled-grid">
            <?php foreach ($handled as $item): ?>
                <li class="handled-tile">
                    <span class="handled-icon"><i class="ti <?= e($item['icon']) ?>" aria-hidden="true"></i></span>
                    <span class="handled-label"><?= e($item['label']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<!-- ============================================================
     Start your journey
     Three routes into the guide, each landing on the section that
     answers that question rather than the top of the page.
     ============================================================ -->
<section class="journey-section">
    <h2 class="journey-heading">Start, or continue, your investment journey</h2>
    <p class="muted journey-sub">
        Three things worth understanding before you put money in. Each one takes about
        a minute to read.
    </p>

    <div class="journey-grid">
        <?php foreach ($journey as $card): ?>
            <a class="journey-tile" href="<?= e($card['href']) ?>">
                <span class="journey-tile-label"><?= e($card['label']) ?></span>
                <span class="journey-tile-blurb"><?= e($card['blurb']) ?></span>
                <span class="journey-tile-fold" aria-hidden="true"></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if (!empty($featured)): ?>
<?php
// Exactly four. Trimmed here rather than in the controller so the section can't
// be broken by a change to whatever HomeController happens to pass in - if it
// sends three, the grid holds; if it sends twenty, only the newest four show.
$recent = array_slice($featured, 0, 4);
?>
<section class="recent-section">
    <div class="section-head">
        <h2>Recently listed</h2>
        <a class="section-action" href="/browse?sort=newest">See all offerings</a>
    </div>

    <div class="recent-grid">
        <?php foreach ($recent as $c): ?>
            <a href="/company/<?= e($c['reference']) ?>" class="recent-card">
                <div class="recent-card-top">
                    <span class="asset-icon"><i class="ti ti-car" aria-hidden="true"></i></span>
                    <span class="ref-badge"><?= e($c['reference']) ?></span>
                </div>
                <h3 class="recent-name"><?= e($c['name']) ?></h3>
                <p class="muted recent-meta">
                    <?= e(trim(($c['make'] ?? '') . ' ' . ($c['model'] ?? ''))) ?><?php
                    if (!empty($c['location'])): ?><br><i class="ti ti-map-pin" aria-hidden="true"></i> <?= e($c['location']) ?><?php endif; ?>
                </p>
                <div class="recent-price">
                    <span class="recent-price-value">R<?= number_format((float) $c['nav_per_share'], 2) ?></span>
                    <span class="recent-price-label">NAV / share</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<script src="/assets/js/logo-strip.js" defer></script>
