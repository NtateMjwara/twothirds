<?php
/**
 * The TwoThirds lockup for the navbar: emblem image, rule, wordmark.
 *
 * Drop the emblem at public_html/assets/img/logo/emblem.png. If you have a
 * 2x export, add it as emblem@2x.png and the srcset below picks it up on
 * retina screens; if you don't, the srcset line can be deleted and the plain
 * src still works.
 *
 * Optional variables:
 *   $brandHref  where the lockup links (default '/')
 *   $brandTag   short suffix badge, e.g. 'Admin'
 *
 * The wordmark stays live text rather than being baked into the image: it stays
 * selectable and searchable, screen readers announce "TwoThirds" instead of
 * spelling out capitals, and it renders crisply at any size. The all-caps is a
 * CSS transform for the same reason.
 */
$brandHref = $brandHref ?? '/';
$brandTag  = $brandTag ?? '';
?>
<a href="<?= e($brandHref) ?>" class="brand" aria-label="TwoThirds home">
    <img class="brand-emblem"
         src="/assets/img/logo/emblem.png"
         srcset="/assets/img/logo/emblem.png 1x, /assets/img/logo/emblem@2x.png 2x"
         alt="" aria-hidden="true">
    <span class="brand-rule" aria-hidden="true"></span>
    <span class="brand-name">TwoThirds</span>
    <?php if ($brandTag !== ''): ?>
        <span class="admin-tag"><?= e($brandTag) ?></span>
    <?php endif; ?>
</a>
