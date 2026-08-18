<p><a href="/company/<?= e($company['reference']) ?>">&larr; Back to <?= e($company['name']) ?></a></p>
<div class="page-title-row">
    <h1>Asset photographs</h1>
    <span class="ref-badge"><?= e($company['reference']) ?></span>
</div>
<?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>

<div class="settings-card" style="max-width:760px;">
    <h2 style="margin-top:0;">Upload</h2>
    <p class="muted" style="font-size:0.88rem;">
        JPG, PNG or WebP, at least 600&times;400, up to 12MB each. Landscape shots work best &mdash;
        the page hero is a wide banner and a portrait photo gets cropped hard at the top and bottom.
        Everything is re-encoded on upload, which strips the EXIF block; that matters because
        phone photos usually carry the GPS coordinates of wherever they were taken.
    </p>

    <form method="post" action="/admin/companies/<?= e($company['reference']) ?>/images" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>Images
            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
        </label>
        <p class="muted" style="font-size:0.82rem; margin-top:-0.4rem;">
            Select several at once. Captions can be added after uploading.
        </p>
        <button type="submit" class="btn"><i class="ti ti-upload" aria-hidden="true"></i> Upload</button>
    </form>
</div>

<h2>On this company (<?= count($images) ?>)</h2>

<?php if (!$images): ?>
    <p class="muted">
        No photographs yet. The company page falls back to a placeholder and says the record is
        complete without them &mdash; but a listing with pictures is a far easier decision for an
        investor than one without.
    </p>
<?php else: ?>
    <p class="muted" style="font-size:0.88rem;">
        The cover is used as the page hero and on discovery cards. Deleting the cover promotes
        the next image automatically.
    </p>

    <div class="admin-image-grid">
        <?php foreach ($images as $image): ?>
            <div class="admin-image<?= $image['is_primary'] ? ' is-primary' : '' ?>">
                <img src="/uploads/assets/<?= e($image['thumb_path'] ?: $image['file_path']) ?>"
                     alt="<?= e($image['caption'] ?: 'Asset photograph') ?>" loading="lazy">

                <?php if ($image['is_primary']): ?>
                    <span class="admin-image-badge"><i class="ti ti-star-filled" aria-hidden="true"></i> Cover</span>
                <?php endif; ?>

                <div class="admin-image-actions">
                    <?php if (!$image['is_primary']): ?>
                        <form method="post" action="/admin/companies/<?= e($company['reference']) ?>/images/<?= (int) $image['id'] ?>/primary">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn-compact">Make cover</button>
                        </form>
                    <?php endif; ?>

                    <form method="post" action="/admin/companies/<?= e($company['reference']) ?>/images/<?= (int) $image['id'] ?>/delete"
                          onsubmit="return confirm('Delete this photograph? The file is removed from disk and cannot be recovered.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-compact btn-danger">Delete</button>
                    </form>
                </div>

                <?php if ($image['caption']): ?>
                    <p class="admin-image-caption"><?= e($image['caption']) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
