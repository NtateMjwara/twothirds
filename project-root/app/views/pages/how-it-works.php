<section class="discovery-masthead">
    <p class="kicker-sm">How it works</p>
    <h1>One company, one asset, one activity</h1>
    <p class="muted discovery-lede">
        The structure is deliberately boring. Every listing on this platform is a
        separate company that owns exactly one registered asset and earns from one
        commercial activity. Nothing is pooled, so what you own is always traceable
        to a specific vehicle doing specific work.
    </p>
</section>

<section class="rail-section" aria-labelledby="terms-heading">
    <div class="section-head">
        <h2 id="terms-heading">The four terms you'll see everywhere</h2>
    </div>

    <div class="theme-rail">
        <div class="theme-card">
            <i class="ti ti-certificate" aria-hidden="true"></i>
            <span class="theme-label">Shares</span>
            <span class="theme-blurb muted">
                Your stake in the company that owns the asset. Held in the share register,
                not in a fund.
            </span>
        </div>
        <div class="theme-card">
            <i class="ti ti-calculator" aria-hidden="true"></i>
            <span class="theme-label">NAV per share</span>
            <span class="theme-blurb muted">
                Net asset value divided by shares issued. It moves when the asset is
                revalued or the company's cash position changes.
            </span>
        </div>
        <div class="theme-card">
            <i class="ti ti-activity-heartbeat" aria-hidden="true"></i>
            <span class="theme-label">Utilisation</span>
            <span class="theme-blurb muted">
                The share of available operating time the asset actually earned in its
                last reported period.
            </span>
        </div>
        <div class="theme-card">
            <i class="ti ti-shield-bolt" aria-hidden="true"></i>
            <span class="theme-label">Asset replacement fund</span>
            <span class="theme-blurb muted">
                A portion of income set aside each period so the asset can be replaced
                at the end of its working life rather than sold at a loss.
            </span>
        </div>
    </div>
</section>

<section class="tile-section" aria-labelledby="steps-heading">
    <div class="section-head">
        <h2 id="steps-heading">From browsing to owning</h2>
    </div>

    <ol class="how-steps">
        <li>
            <span class="how-step-index">01</span>
            <div>
                <h3>Find an offering</h3>
                <p class="muted">
                    Filter by industry, activity, asset class or operating area. Every card
                    shows NAV per share, shares still available and how subscribed the
                    company already is.
                </p>
            </div>
        </li>
        <li>
            <span class="how-step-index">02</span>
            <div>
                <h3>Read the company, not the pitch</h3>
                <p class="muted">
                    Each listing carries its registration details, directors, the asset's
                    valuation and roadworthy status, and every financial period it has
                    filed. Documents are attached to the record.
                </p>
            </div>
        </li>
        <li>
            <span class="how-step-index">03</span>
            <div>
                <h3>Commit</h3>
                <p class="muted">
                    A commitment reserves shares at the current NAV and expires if it isn't
                    settled. Reserved shares come out of the available count immediately, so
                    what you see on a card is what's genuinely left.
                </p>
            </div>
        </li>
        <li>
            <span class="how-step-index">04</span>
            <div>
                <h3>Settlement puts you on the register</h3>
                <p class="muted">
                    Once payment is confirmed, an administrator settles the commitment and
                    your shares are written to the register. It's an append-only ledger,
                    so corrections are new entries and history stays intact.
                </p>
            </div>
        </li>
    </ol>
</section>

<section class="rail-section" aria-labelledby="sectors-heading">
    <div class="section-head">
        <h2 id="sectors-heading">Where assets earn</h2>
        <a class="section-action" href="/discover">Browse all <?= number_format($totalActive) ?></a>
    </div>

    <div class="chip-rail" role="group" aria-label="Industries">
        <?php foreach ($sectors as $s): ?>
            <a class="rail-chip<?= (int) $s['listing_count'] === 0 ? ' is-empty' : '' ?>"
               href="/discover?sector=<?= e($s['slug']) ?>" title="<?= e($s['tagline'] ?? '') ?>">
                <span class="rail-chip-icon"><i class="ti <?= e($s['icon']) ?>" aria-hidden="true"></i></span>
                <span class="rail-chip-label"><?= e($s['name']) ?></span>
                <span class="rail-chip-count"><?= number_format((int) $s['listing_count']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<script src="/assets/js/discovery.js" defer></script>
