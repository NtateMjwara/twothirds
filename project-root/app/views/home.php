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
            <p class="kicker-tag">Asset Management</p>
        </div>
        <div class="hero-body">
            <h1>Get Rich Slowly.</h1>
            <p class="hero-lede">
                TwoThirds gives you the tools to build long-term wealth
                by gradually accumulating small pieces of productive assets
                 in South Africa.
            </p>
            <p><a href="/discover" class="btn"><i class="ti ti-search" aria-hidden="true"></i> Explore Our Latest Offering</a></p>
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
        Be more than just a consumer. Make small investments and own a part of the economy.
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

<section>
    <h2>How it works</h2>
    <div class="card-grid">
        <div class="card" style="width:auto; flex:1; min-width:200px;">
            <div class="asset-icon"><i class="ti ti-list-search" aria-hidden="true"></i></div>
            <h3>Browse</h3>
            <p class="muted">Every company discloses its asset, financials, governance, and documents publicly.</p>
        </div>
        <div class="card" style="width:auto; flex:1; min-width:200px;">
            <div class="asset-icon"><i class="ti ti-file-search" aria-hidden="true"></i></div>
            <h3>Review</h3>
            <p class="muted">Check the corporate record, the asset's condition, and the trading history before deciding anything.</p>
        </div>
        <div class="card" style="width:auto; flex:1; min-width:200px;">
            <div class="asset-icon"><i class="ti ti-circle-check" aria-hidden="true"></i></div>
            <h3>Commit</h3>
            <p class="muted">Signal interest in shares. No payment is taken online - settlement is arranged and confirmed directly.</p>
        </div>
    </div>
</section>

<?php if (!empty($featured)): ?>
<section>
    <h2>Recently listed</h2>
    <div class="featured-carousel-wrap">
        <button class="carousel-arrow carousel-prev" aria-label="Previous"><i class="ti ti-chevron-left" aria-hidden="true"></i></button>
        <div class="featured-carousel">
        <?php foreach ($featured as $c): ?>
            <a href="/company/<?= e($c['reference']) ?>" class="card" style="display:block; text-decoration:none; color:inherit;">
                <div class="card-top">
                    <div class="asset-icon"><i class="ti ti-car" aria-hidden="true"></i></div>
                    <span class="ref-badge"><?= e($c['reference']) ?></span>
                </div>
                <h3><?= e($c['name']) ?></h3>
                <p class="muted"><?= e(trim(($c['make'] ?? '') . ' ' . ($c['model'] ?? ''))) ?><?= $c['location'] ? ' &middot; ' . e($c['location']) : '' ?></p>
                <div class="stat">
                    <span class="stat-value">R<?= number_format((float) $c['nav_per_share'], 2) ?></span>
                    <span class="stat-label">NAV/share</span>
                </div>
            </a>
        <?php endforeach; ?>
        </div>
        <button class="carousel-arrow carousel-next" aria-label="Next"><i class="ti ti-chevron-right" aria-hidden="true"></i></button>
    </div>
</section>
<?php endif; ?>

<script src="/assets/js/logo-strip.js" defer></script>
