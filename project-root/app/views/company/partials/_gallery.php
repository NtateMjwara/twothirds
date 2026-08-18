<?php
/**
 * Asset gallery.
 *
 * Expects: $images (ordered, primary first), $company, $asset.
 *
 * The hero is a plain <img>, not a CSS background, so it can carry alt text and
 * be found by an image search. The lightbox markup is rendered inline rather
 * than built in JavaScript so that without the script the page still shows every
 * photograph — the thumbnails simply become anchors to the full-size files.
 */
$cover = $images[0] ?? null;
$assetName = trim(((string) ($asset['make'] ?? '')) . ' ' . ((string) ($asset['model'] ?? '')));
$coverAlt = $cover && $cover['caption']
    ? $cover['caption']
    : ($assetName !== '' ? $assetName . ' owned by ' . $company['name'] : $company['name'] . "'s asset");
?>
<div class="company-hero<?= $cover ? '' : ' is-empty' ?>">
    <a href="/discover" class="hero-back">
        <i class="ti ti-arrow-left" aria-hidden="true"></i> Back to all offerings
    </a>

    <?php if ($cover): ?>
        <img class="hero-image" src="/uploads/assets/<?= e($cover['file_path']) ?>"
             alt="<?= e($coverAlt) ?>" fetchpriority="high">

        <p class="hero-disclaimer">
            Photographs are of the actual asset owned by this company, taken at or
            near the valuation date. Condition changes with use &mdash; the trading
            history and inspection records are the current position.
        </p>

        <?php if (count($images) > 1): ?>
            <button type="button" class="hero-gallery-btn" data-gallery-open="0">
                <i class="ti ti-photo" aria-hidden="true"></i>
                View <?= count($images) ?> photos
            </button>
        <?php endif; ?>
    <?php else: ?>
        <div class="hero-placeholder">
            <i class="ti ti-camera-off" aria-hidden="true"></i>
            <p>No photographs uploaded yet</p>
            <p class="hero-placeholder-note">
                The corporate record, asset details and trading history below are complete
                whether or not there are pictures.
            </p>
        </div>
    <?php endif; ?>
</div>

<?php if (count($images) > 1): ?>
    <div class="hero-thumbs">
        <?php foreach ($images as $i => $image): ?>
            <button type="button" class="hero-thumb<?= $i === 0 ? ' is-active' : '' ?>"
                    data-gallery-open="<?= (int) $i ?>"
                    aria-label="View photo <?= $i + 1 ?> of <?= count($images) ?>">
                <img src="/uploads/assets/<?= e($image['thumb_path'] ?: $image['file_path']) ?>"
                     alt="" loading="lazy">
            </button>
        <?php endforeach; ?>
    </div>

    <dialog class="gallery-dialog" id="assetGallery" aria-label="Asset photographs">
        <button type="button" class="gallery-close" data-gallery-close aria-label="Close gallery">
            <i class="ti ti-x" aria-hidden="true"></i>
        </button>

        <button type="button" class="gallery-nav gallery-prev" data-gallery-step="-1" aria-label="Previous photo">
            <i class="ti ti-chevron-left" aria-hidden="true"></i>
        </button>

        <figure class="gallery-stage">
            <?php foreach ($images as $i => $image): ?>
                <img class="gallery-slide<?= $i === 0 ? ' is-current' : '' ?>"
                     data-gallery-slide="<?= (int) $i ?>"
                     src="/uploads/assets/<?= e($image['file_path']) ?>"
                     alt="<?= e($image['caption'] ?: $assetName . ' photograph ' . ($i + 1)) ?>"
                     loading="lazy">
            <?php endforeach; ?>
            <figcaption class="gallery-caption">
                <span data-gallery-caption><?= e($images[0]['caption'] ?? '') ?></span>
                <span class="gallery-counter"><span data-gallery-index>1</span> / <?= count($images) ?></span>
            </figcaption>
        </figure>

        <button type="button" class="gallery-nav gallery-next" data-gallery-step="1" aria-label="Next photo">
            <i class="ti ti-chevron-right" aria-hidden="true"></i>
        </button>
    </dialog>
<?php endif; ?>
