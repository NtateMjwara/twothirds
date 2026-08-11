<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TwoThirds Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&family=Playfair+Display:ital,wght@1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.46.0/dist/tabler-icons.min.css">
    <?php $cssPath = __DIR__ . '/../../../public_html/assets/css/app.css'; ?>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=<?= file_exists($cssPath) ? filemtime($cssPath) : time() ?>">
</head>
<body class="admin-body">
    <header class="site-header">
        <div class="site-header-inner">
            <a href="/admin" class="wordmark">TwoThirds<span class="wordmark-dot">.</span><span class="admin-tag">Admin</span></a>
            <?php if (!empty($_SESSION['admin_id'])): ?>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="siteNav">
                <i class="ti ti-menu-2" aria-hidden="true"></i>
            </button>
            <nav class="site-nav" id="siteNav">
                <span class="muted" style="font-size:0.85rem;"><?= e($_SESSION['admin_name']) ?> &middot; <?= e($_SESSION['admin_role']) ?></span>
                <a href="/admin/logout"><i class="ti ti-logout" aria-hidden="true"></i> Log out</a>
            </nav>
            <?php endif; ?>
        </div>
    </header>

    <main class="site-main">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="footer-bottom" style="border-top:none; padding-top:1.5rem;">
            <p class="muted">&copy; <?= date('Y') ?> TwoThirds Admin.</p>
        </div>
    </footer>

    <script src="/assets/js/nav-toggle.js"></script>
</body>
</html>
