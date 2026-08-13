<?php
/**
 * A saved listing, drawn as a snapshot: what it's worth per share now, how
 * subscribed it is, and the shape of its trading income.
 *
 * Expects: $w (snapshot row from DiscoveryService).
 *
 * The sparkline plots net operating income per filed period. A company with no
 * filed periods gets a flat rule and the label says why - drawing a fake curve
 * on an SPV that hasn't traded yet would be the one dishonest pixel on the page.
 */
$points = $w['spark'] ?? [];
$change = $w['spark_change'] ?? null;
$width = 120;
$height = 34;

if (count($points) >= 2) {
    $min = min($points);
    $max = max($points);
    $range = ($max - $min) ?: 1;
    $step = $width / (count($points) - 1);

    $coords = [];
    foreach ($points as $i => $value) {
        $x = round($i * $step, 1);
        // SVG y grows downward; invert so a rising income reads as a rising line.
        $y = round($height - 3 - (($value - $min) / $range) * ($height - 6), 1);
        $coords[] = "{$x},{$y}";
    }
    $polyline = implode(' ', $coords);
} else {
    $polyline = '0,' . ($height / 2) . ' ' . $width . ',' . ($height / 2);
}

$trendClass = $change === null ? 'is-flat' : ($change >= 0 ? 'is-up' : 'is-down');
?>
<a href="/company/<?= e($w['reference']) ?>" class="snapshot-card">
    <div class="snapshot-head">
        <div class="snapshot-identity">
            <span class="snapshot-icon"><i class="ti <?= e($w['sector_icon'] ?: 'ti-car') ?>" aria-hidden="true"></i></span>
            <span class="snapshot-name"><?= e($w['name']) ?></span>
        </div>
        <svg class="sparkline <?= $trendClass ?>" viewBox="0 0 <?= $width ?> <?= $height ?>" width="<?= $width ?>" height="<?= $height ?>" role="img"
             aria-label="<?= count($points) >= 2 ? 'Net operating income across the last ' . count($points) . ' filed periods' : 'No filed trading periods yet' ?>">
            <polyline points="<?= e($polyline) ?>" fill="none" stroke="currentColor" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
        </svg>
    </div>

    <div class="snapshot-figures">
        <div class="snapshot-figure">
            <span class="snapshot-value <?= $trendClass ?>">
                <?= $change === null ? '&mdash;' : ($change > 0 ? '+' : '') . number_format($change, 2) . '%' ?>
            </span>
            <span class="snapshot-label"><?= $change === null ? 'No periods filed' : 'Last period NOI' ?></span>
        </div>
        <div class="snapshot-figure snapshot-figure-right">
            <span class="snapshot-value">R<?= number_format((float) $w['nav_per_share'], 2) ?></span>
            <span class="snapshot-label">NAV / share</span>
        </div>
    </div>

    <div class="funded-bar" role="progressbar" aria-valuenow="<?= (int) $w['funded_pct'] ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Subscribed">
        <div class="funded-bar-fill" style="width:<?= (int) $w['funded_pct'] ?>%;"></div>
    </div>

    <p class="snapshot-foot muted">
        <?= (int) $w['funded_pct'] ?>% subscribed<?php if (!empty($w['activity_name'])): ?> &middot; <?= e($w['activity_name']) ?><?php endif; ?>
    </p>
</a>
