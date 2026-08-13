<?php
/**
 * Brand <head>: icons, theme colour, social preview.
 *
 * Two different consumers with two different requirements:
 *
 * BROWSER TABS take the first icon they understand. Browsers that support SVG
 * favicons use favicon.svg and ignore everything after it; the rest fall
 * through to favicon.ico, which carries purpose-drawn 16, 32 and 48px entries.
 *
 * GOOGLE SEARCH ignores 16px icons entirely. It wants a square icon whose size
 * is a multiple of 48px, which is what the 96px and 192px PNGs below are for.
 * Google also requires the file to sit at a stable URL that isn't blocked in
 * robots.txt, and it only picks the icon up on its next crawl of the site root —
 * expect days, not minutes.
 */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$origin = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'twothirds.co.za');
?>
<link rel="icon" href="/favicon.ico" sizes="48x48">
<link rel="icon" href="/assets/img/logo/favicon.svg" type="image/svg+xml">
<link rel="icon" type="image/png" sizes="96x96" href="/assets/img/logo/favicon-96.png">
<link rel="icon" type="image/png" sizes="192x192" href="/assets/img/logo/icon-192.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/img/logo/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
<meta name="theme-color" content="#1B2028">

<meta property="og:site_name" content="TwoThirds">
<meta property="og:type" content="website">
<meta property="og:image" content="<?= e($origin) ?>/assets/img/logo/og-image.png">
<meta name="twitter:card" content="summary">
