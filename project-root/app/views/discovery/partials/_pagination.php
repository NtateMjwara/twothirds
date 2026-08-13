<?php
/**
 * Result pagination.
 *
 * Expects: $result (page, pages, total, per_page) and $filters.
 * Links are plain GET URLs built from the current filter set, so a page deep in
 * a filtered result is shareable and survives a refresh.
 */
$page = (int) $result['page'];
$pages = (int) $result['pages'];

if ($pages <= 1) {
    return;
}

// Always show the first and last page; show a window of two either side of the
// current one. Everything else collapses into a gap marker.
$window = [];
foreach (range(1, $pages) as $n) {
    if ($n === 1 || $n === $pages || abs($n - $page) <= 2) {
        $window[] = $n;
    }
}

$first = (($page - 1) * (int) $result['per_page']) + 1;
$last = min((int) $result['total'], $page * (int) $result['per_page']);
?>
<nav class="pagination" aria-label="Offering pages">
    <p class="pagination-status muted">
        Showing <strong><?= number_format($first) ?>&ndash;<?= number_format($last) ?></strong>
        of <?= number_format((int) $result['total']) ?>
    </p>

    <ul class="pagination-list">
        <li>
            <?php if ($page > 1): ?>
                <a class="page-link page-step" href="/discover<?= e(query_with($filters, ['page' => $page - 1])) ?>" rel="prev">
                    <i class="ti ti-chevron-left" aria-hidden="true"></i><span class="page-step-label">Previous</span>
                </a>
            <?php else: ?>
                <span class="page-link page-step is-disabled" aria-disabled="true">
                    <i class="ti ti-chevron-left" aria-hidden="true"></i><span class="page-step-label">Previous</span>
                </span>
            <?php endif; ?>
        </li>

        <?php $previous = 0; ?>
        <?php foreach ($window as $n): ?>
            <?php if ($previous && $n - $previous > 1): ?>
                <li><span class="page-gap" aria-hidden="true">&hellip;</span></li>
            <?php endif; ?>
            <li>
                <?php if ($n === $page): ?>
                    <span class="page-link is-current" aria-current="page"><?= $n ?></span>
                <?php else: ?>
                    <a class="page-link" href="/discover<?= e(query_with($filters, ['page' => $n])) ?>"><?= $n ?></a>
                <?php endif; ?>
            </li>
            <?php $previous = $n; ?>
        <?php endforeach; ?>

        <li>
            <?php if ($page < $pages): ?>
                <a class="page-link page-step" href="/discover<?= e(query_with($filters, ['page' => $page + 1])) ?>" rel="next">
                    <span class="page-step-label">Next</span><i class="ti ti-chevron-right" aria-hidden="true"></i>
                </a>
            <?php else: ?>
                <span class="page-link page-step is-disabled" aria-disabled="true">
                    <span class="page-step-label">Next</span><i class="ti ti-chevron-right" aria-hidden="true"></i>
                </span>
            <?php endif; ?>
        </li>
    </ul>
</nav>
