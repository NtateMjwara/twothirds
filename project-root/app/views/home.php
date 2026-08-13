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
                across industries in South Africa.
            </p>
            <p><a href="/discover" class="btn"><i class="ti ti-search" aria-hidden="true"></i> Explore Our Latest Offering</a></p>
        </div>
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
