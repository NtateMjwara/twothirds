<?php
/**
 * Breadcrumbs.
 *
 * Expects: $crumbs, a list of ['label' => string, 'href' => ?string].
 * The last entry should have a null href - it is where you are, not somewhere
 * to go.
 *
 * Marked up as an ordered list inside <nav aria-label="Breadcrumb">, which is
 * what assistive technology expects, with the current page carrying
 * aria-current. A row of links separated by slashes reads as a menu instead.
 */
if (empty($crumbs)) {
    return;
}
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol>
        <?php foreach ($crumbs as $i => $crumb): ?>
            <li>
                <?php if (!empty($crumb['href'])): ?>
                    <a href="<?= e($crumb['href']) ?>"><?= e($crumb['label']) ?></a>
                <?php else: ?>
                    <span aria-current="page"><?= e($crumb['label']) ?></span>
                <?php endif; ?>
                <?php if ($i < count($crumbs) - 1): ?>
                    <i class="ti ti-chevron-right" aria-hidden="true"></i>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
