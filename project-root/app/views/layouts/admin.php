<?php
use app\core\Controller;
use app\services\AdminPolicy;

$adminRole = $_SESSION['admin_role'] ?? null;
$navItems = AdminPolicy::navigation($adminRole);
$flashes = Controller::takeFlash();

// Longest-prefix match, so /admin/companies/SPV-00801/edit still highlights
// Companies while /admin doesn't win on every page by being a prefix of all.
$path = strtok($_SERVER['REQUEST_URI'] ?? '/admin', '?');
$activeHref = '';
foreach ($navItems as $item) {
    if ($path === $item['href'] || str_starts_with($path, $item['href'] . '/')) {
        if (strlen($item['href']) > strlen($activeHref)) {
            $activeHref = $item['href'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TwoThirds Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <?php require __DIR__ . '/../partials/_brand-head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&family=Playfair+Display:ital,wght@1,600&family=Marcellus&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.46.0/dist/tabler-icons.min.css">
    <?php
    $stylesheets = ['app.css', 'brand.css', 'company.css', 'admin.css'];
    foreach ($stylesheets as $sheet):
        $sheetPath = __DIR__ . '/../../../public_html/assets/css/' . $sheet;
    ?>
    <link rel="stylesheet" href="<?= asset('css/' . $sheet) ?>?v=<?= file_exists($sheetPath) ? filemtime($sheetPath) : time() ?>">
    <?php endforeach; ?>
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="admin-header-inner">
            <?php $brandHref = '/admin'; $brandTag = 'Admin'; require __DIR__ . '/../partials/_brand-lockup.php'; ?>

            <?php if (!empty($_SESSION['admin_id'])): ?>
                <button class="nav-toggle admin-nav-toggle" id="adminNavToggle"
                        aria-label="Toggle admin menu" aria-expanded="false" aria-controls="adminNav">
                    <i class="ti ti-menu-2" aria-hidden="true"></i>
                </button>

                <nav class="admin-nav" id="adminNav" aria-label="Admin sections">
                    <?php foreach ($navItems as $item): ?>
                        <a href="<?= e($item['href']) ?>"
                           class="admin-nav-link<?= $item['href'] === $activeHref ? ' is-active' : '' ?>"
                           <?= $item['href'] === $activeHref ? 'aria-current="page"' : '' ?>>
                            <i class="ti <?= e($item['icon']) ?>" aria-hidden="true"></i>
                            <span><?= e($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="admin-identity">
                    <span class="admin-identity-name"><?= e($_SESSION['admin_name'] ?? 'Admin') ?></span>
                    <span class="admin-role-badge role-<?= e($adminRole ?? 'unknown') ?>">
                        <?= e(AdminPolicy::label($adminRole)) ?>
                    </span>
                    <a href="/admin/logout" class="admin-logout" title="Log out">
                        <i class="ti ti-logout" aria-hidden="true"></i><span class="sr-only">Log out</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="site-main admin-main">
        <?php if ($flashes): ?>
            <div class="flash-stack" role="status" aria-live="polite">
                <?php foreach ($flashes as $flash): ?>
                    <p class="flash flash-<?= e($flash['type']) ?>">
                        <i class="ti <?= $flash['type'] === 'success' ? 'ti-circle-check' : ($flash['type'] === 'error' ? 'ti-alert-circle' : 'ti-info-circle') ?>" aria-hidden="true"></i>
                        <?= e($flash['message']) ?>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="admin-footer">
        <p class="muted">
            &copy; <?= date('Y') ?> TwoThirds Admin.
            Every action on these pages is recorded against your account in the audit log.
        </p>
    </footer>

    <script src="/assets/js/admin-nav.js" defer></script>
</body>
</html>
