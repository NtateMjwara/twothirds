<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investment Management & Financial Services | TwoThirds</title>
    <?php require __DIR__ . '/../partials/_brand-head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&family=Playfair+Display:ital,wght@1,600&family=Marcellus&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.46.0/dist/tabler-icons.min.css">
    <?php
    /*
     * One loop rather than a dozen near-identical blocks.
     *
     * ORDER IS LOAD ORDER. Each sheet may override the one before it, so this
     * list is a cascade, not a set - which is why it can't be replaced with a
     * glob of the directory.
     *
     * Two rules that have already been broken once:
     *   - cards.css must come AFTER discovery.css. It resets
     *     .offering-card-link's padding to 0 and positions the cover overlays;
     *     load it first and the card renders with the reference badge and
     *     sector chip flowing below the photograph instead of on it.
     *   - Anything new goes in this list. A stylesheet that exists on disk but
     *     isn't named here is silently ignored, and the page renders half-styled
     *     with nothing to indicate why. tools/preflight.php checks for exactly
     *     that.
     */
    $stylesheets = [
        'app.css',
        'brand.css',
        'nav.css',
        'breadcrumbs.css',
        'home.css',
        'pages.css',
        'discovery.css',
        'cards.css',        // after discovery.css - see above
        'company.css',
        'account.css',
        'portfolio.css',
    ];
    foreach ($stylesheets as $sheet):
        $sheetPath = __DIR__ . '/../../../public_html/assets/css/' . $sheet;
        if (!file_exists($sheetPath)) { continue; }
    ?>
    <link rel="stylesheet" href="<?= asset('css/' . $sheet) ?>?v=<?= filemtime($sheetPath) ?>">
    <?php endforeach; ?>
</head>
<body>
    <?php require __DIR__ . '/../partials/_site-nav.php'; ?>

    <main class="site-main" id="main">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="site-footer-inner">
            <div>
                <?php $brandHref = '/'; $brandTag = ''; require __DIR__ . '/../partials/_brand-lockup.php'; ?>
                <p class="muted" style="font-size:0.85rem; margin-top:10px; max-width:320px;">
                    A public record of companies that own real, income-producing assets.
                </p>
            </div>
            <nav class="footer-links" aria-label="Footer">
                <a href="/discover">Discover</a>
                <a href="<?= e(invest_url()) ?>">Offerings</a>
                <a href="/how-it-works">How it works</a>
                <a href="/fees">Fees</a>
                <a href="/login">Log in</a>
            </nav>
        </div>
        <div class="footer-bottom">
            <p class="muted">&copy; <?= date('Y') ?> TwoThirds. Company and asset information is published by the companies themselves.</p>
        </div>
    </footer>

    <script src="/assets/js/site-nav.js" defer></script>
    <script src="/assets/js/carousel.js" defer></script>
</body>
</html>
