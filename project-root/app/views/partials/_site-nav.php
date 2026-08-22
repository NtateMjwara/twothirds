<?php
/**
 * Site navigation.
 *
 * Three zones, which is what the old flat row of six links was missing:
 *
 *   Brand      home
 *   Primary    where to go - Discover, Browse, How it works, Fees
 *   Account    who you are - notifications, then everything about you behind
 *              one avatar
 *
 * Personal links used to sit in the same row as public ones, so the bar grew
 * every time the account section did and nothing was grouped by what it was for.
 *
 * Everything here works without JavaScript: the dropdown is a <details>, the
 * mobile panel is a checkbox-free <details> too, and each item is a plain link.
 */

use app\services\NavContext;

$nav = NavContext::get();
$path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

$primary = [
    ['label' => 'Discover',     'href' => '/discover',        'match' => ['/discover']],
    ['label' => 'Offerings',    'href' => '/discover/invest', 'match' => ['/discover/invest']],
    ['label' => 'How it works', 'href' => '/how-it-works',    'match' => ['/how-it-works']],
    ['label' => 'Fees',         'href' => '/fees',            'match' => ['/fees']],
];

/**
 * Longest match wins.
 *
 * /discover/invest starts with /discover, so a first-match-wins loop would
 * light up Discover on every offerings page and every company page. Scoring by
 * the length of the matched prefix picks the more specific one.
 */
$activeHref = '';
$activeScore = 0;
foreach ($primary as $item) {
    foreach ($item['match'] as $prefix) {
        if (($path === $prefix || str_starts_with($path, $prefix . '/')) && strlen($prefix) > $activeScore) {
            $activeScore = strlen($prefix);
            $activeHref = $item['href'];
        }
    }
}

$isActive = static fn (array $item): bool => $item['href'] === $activeHref && $activeHref !== '';

// Grouped, because a flat list of nine account links is a wall. Each group is a
// different reason to be there: your money, your details, your security.
$accountGroups = [
    [
        ['label' => 'Portfolio',     'href' => '/account/portfolio',     'icon' => 'ti-chart-pie'],
        ['label' => 'Watchlist',     'href' => '/account/watchlist',     'icon' => 'ti-bookmark'],
        ['label' => 'Notifications', 'href' => '/account/notifications', 'icon' => 'ti-bell', 'badge' => $nav['unread']],
    ],
    [
        ['label' => 'Personal details', 'href' => '/account/profile', 'icon' => 'ti-user'],
        ['label' => 'Bank accounts',    'href' => '/account/bank',    'icon' => 'ti-building-bank'],
        ['label' => 'Tax info',         'href' => '/account/tax',     'icon' => 'ti-receipt-tax'],
    ],
];
?>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
    <div class="site-header-inner">
        <?php $brandHref = '/'; $brandTag = ''; require __DIR__ . '/_brand-lockup.php'; ?>

        <!-- Primary navigation. Same list on desktop and in the mobile panel,
             rendered once and repositioned by CSS. -->
        <nav class="primary-nav" aria-label="Main">
            <?php foreach ($primary as $item): ?>
                <a href="<?= e($item['href']) ?>"
                   class="primary-link<?= $isActive($item) ? ' is-active' : '' ?>"
                   <?= $isActive($item) ? 'aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <?php if ($nav['signed_in']): ?>

                <a href="/account/notifications" class="icon-action<?= $nav['unread'] > 0 ? ' has-badge' : '' ?>"
                   aria-label="Notifications<?= $nav['unread'] > 0 ? ', ' . $nav['unread'] . ' unread' : '' ?>">
                    <i class="ti ti-bell" aria-hidden="true"></i>
                    <?php if ($nav['unread'] > 0): ?>
                        <span class="icon-badge"><?= $nav['unread'] > 9 ? '9+' : (int) $nav['unread'] ?></span>
                    <?php endif; ?>
                </a>

                <details class="profile-menu" id="profileMenu">
                    <summary class="profile-trigger" aria-label="Your account">
                        <span class="profile-avatar<?= $nav['needs_attention'] ? ' needs-attention' : '' ?>">
                            <?= e($nav['initials']) ?>
                        </span>
                        <i class="ti ti-chevron-down profile-caret" aria-hidden="true"></i>
                    </summary>

                    <div class="profile-panel" role="menu">
                        <a class="profile-head" href="/account/profile" role="menuitem">
                            <span class="profile-avatar profile-avatar-lg"><?= e($nav['initials']) ?></span>
                            <span class="profile-head-text">
                                <span class="profile-head-name"><?= e($nav['full_name']) ?></span>
                                <span class="profile-head-sub muted">Personal details, KYC, banking</span>
                            </span>
                            <i class="ti ti-chevron-right" aria-hidden="true"></i>
                        </a>

                        <dl class="profile-meta">
                            <div>
                                <dt>Investor ID</dt>
                                <dd class="is-mono">TT-<?= e(str_pad((string) $nav['id'], 6, '0', STR_PAD_LEFT)) ?></dd>
                            </div>
                            <div>
                                <dt>Verification</dt>
                                <dd>
                                    <?php if ($nav['verified']): ?>
                                        <span class="verify-pill is-verified"><i class="ti ti-circle-check" aria-hidden="true"></i> Verified</span>
                                    <?php elseif ($nav['kyc_status'] === 'pending'): ?>
                                        <span class="verify-pill is-pending"><i class="ti ti-clock" aria-hidden="true"></i> In review</span>
                                    <?php elseif ($nav['kyc_status'] === 'rejected'): ?>
                                        <span class="verify-pill is-rejected"><i class="ti ti-alert-circle" aria-hidden="true"></i> Action needed</span>
                                    <?php else: ?>
                                        <span class="verify-pill is-none"><i class="ti ti-minus" aria-hidden="true"></i> Not started</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        </dl>

                        <?php if (!$nav['verified']): ?>
                            <!-- The one thing standing between this person and
                                 committing to an offering, so it sits above the
                                 ordinary links rather than inside them. -->
                            <a class="profile-nudge" href="/account/kyc" role="menuitem">
                                <i class="ti ti-shield-check" aria-hidden="true"></i>
                                <span>
                                    <strong><?= $nav['kyc_status'] === 'rejected' ? 'Fix your verification' : 'Finish verifying your identity' ?></strong>
                                    <span class="muted">Required before you can commit to an offering</span>
                                </span>
                            </a>
                        <?php endif; ?>

                        <?php foreach ($accountGroups as $group): ?>
                            <div class="profile-group">
                                <?php foreach ($group as $item): ?>
                                    <a class="profile-item" href="<?= e($item['href']) ?>" role="menuitem">
                                        <i class="ti <?= e($item['icon']) ?>" aria-hidden="true"></i>
                                        <span><?= e($item['label']) ?></span>
                                        <?php if (!empty($item['badge'])): ?>
                                            <span class="profile-item-badge"><?= (int) $item['badge'] ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="profile-group">
                            <a class="profile-item" href="/logout" role="menuitem">
                                <i class="ti ti-logout" aria-hidden="true"></i>
                                <span>Log out</span>
                            </a>
                        </div>
                    </div>
                </details>

            <?php else: ?>
                <a href="/login" class="header-login">Log in</a>
                <a href="/register" class="btn header-signup">Sign up</a>
            <?php endif; ?>

            <button class="nav-toggle" id="navToggle" aria-label="Menu"
                    aria-expanded="false" aria-controls="mobilePanel">
                <i class="ti ti-menu-2" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <!-- Mobile panel. A separate element rather than a re-flowed desktop nav:
         the two need different ordering, and hiding half a bar with CSS leaves
         a tab order that walks through invisible controls. -->
    <div class="mobile-panel" id="mobilePanel" hidden>
        <nav aria-label="Main, mobile">
            <?php foreach ($primary as $item): ?>
                <a href="<?= e($item['href']) ?>" class="mobile-link<?= $isActive($item) ? ' is-active' : '' ?>">
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($nav['signed_in']): ?>
            <p class="mobile-group-title">Your account</p>
            <?php foreach ($accountGroups as $group): ?>
                <?php foreach ($group as $item): ?>
                    <a href="<?= e($item['href']) ?>" class="mobile-link">
                        <i class="ti <?= e($item['icon']) ?>" aria-hidden="true"></i>
                        <?= e($item['label']) ?>
                        <?php if (!empty($item['badge'])): ?>
                            <span class="profile-item-badge"><?= (int) $item['badge'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <a href="/logout" class="mobile-link"><i class="ti ti-logout" aria-hidden="true"></i> Log out</a>
        <?php else: ?>
            <div class="mobile-auth">
                <a href="/login" class="btn-outline">Log in</a>
                <a href="/register" class="btn">Sign up</a>
            </div>
        <?php endif; ?>
    </div>
</header>
