<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TwoThirds</title>
    <?php require __DIR__ . '/../partials/_brand-head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&family=Playfair+Display:ital,wght@1,600&family=Marcellus&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.46.0/dist/tabler-icons.min.css">
    <?php $cssPath = __DIR__ . '/../../../public_html/assets/css/app.css'; ?>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=<?= file_exists($cssPath) ? filemtime($cssPath) : time() ?>">
    <?php $brandCss = __DIR__ . '/../../../public_html/assets/css/brand.css'; ?>
    <link rel="stylesheet" href="<?= asset('css/brand.css') ?>?v=<?= file_exists($brandCss) ? filemtime($brandCss) : time() ?>">
    <?php $discoveryCss = __DIR__ . '/../../../public_html/assets/css/discovery.css'; ?>
    <link rel="stylesheet" href="<?= asset('css/discovery.css') ?>?v=<?= file_exists($discoveryCss) ? filemtime($discoveryCss) : time() ?>">
    <?php $homeCss = __DIR__ . '/../../../public_html/assets/css/home.css'; ?>
    <link rel="stylesheet" href="<?= asset('css/home.css') ?>?v=<?= file_exists($homeCss) ? filemtime($homeCss) : time() ?>">
</head>
<body>
    <?php
    use app\models\Notification;
    $unreadCount = !empty($_SESSION['user_id']) ? Notification::unreadCount((int) $_SESSION['user_id']) : 0;
    ?>
    <header class="site-header">
        <div class="site-header-inner">
            <?php $brandHref = '/'; $brandTag = ''; require __DIR__ . '/../partials/_brand-lockup.php'; ?>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="siteNav">
                <i class="ti ti-menu-2" aria-hidden="true"></i>
            </button>
            <nav class="site-nav" id="siteNav">
                <a href="/discover"><i class="ti ti-search" aria-hidden="true"></i> Discover</a>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="/account/watchlist"><i class="ti ti-bookmark" aria-hidden="true"></i> Watchlist</a>
                    <a href="/account/portfolio"><i class="ti ti-briefcase" aria-hidden="true"></i> Portfolio</a>
                    <a href="/account/notifications"><i class="ti ti-bell" aria-hidden="true"></i> Notifications<?= $unreadCount > 0 ? " ({$unreadCount})" : '' ?></a>
                    <a href="/logout"><i class="ti ti-logout" aria-hidden="true"></i> Log out</a>
                <?php else: ?>
                    <a href="/login"><i class="ti ti-login" aria-hidden="true"></i> Log in</a>
                    <a href="/register" class="btn"><i class="ti ti-user-plus" aria-hidden="true"></i> Sign up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="site-main">
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
            <nav class="footer-links">
                <a href="/discover">Discover</a>
                <a href="/login">Log in</a>
                <a href="/register">Sign up</a>
            </nav>
        </div>
        <div class="footer-bottom">
            <p class="muted">&copy; <?= date('Y') ?> TwoThirds. Company and asset information is published by the companies themselves.</p>
        </div>
    </footer>

    <script src="/assets/js/nav-toggle.js"></script>
    <script src="/assets/js/carousel.js"></script>
</body>
</html>
