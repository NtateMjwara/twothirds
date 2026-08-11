<?php require __DIR__ . '/../partials/account-nav.php'; ?>
<h1>Identity verification</h1>
<?php if (!empty($saved)): ?><p class="form-success">Submitted for review.</p><?php endif; ?>
<?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>

<?php if ($kyc): ?>
<div class="settings-card" style="padding:1.25rem 1.75rem;">
    <p style="margin:0 0 8px;">
        Status: <span class="status-badge status-<?= e($kyc['status']) ?>"><?= e($kyc['status']) ?></span>
    </p>
    <?php if ($kyc['status'] === 'rejected' && !empty($kyc['rejection_reason'])): ?>
        <p class="muted" style="margin:0 0 8px;">Reason: <?= e($kyc['rejection_reason']) ?></p>
    <?php endif; ?>
    <?php if ($kyc['document_id']): ?>
        <p style="margin:0;"><a href="/documents/<?= (int) $kyc['document_id'] ?>">View submitted document</a></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="settings-card">
    <form method="post" action="/account/kyc" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>ID type
            <select name="id_type">
                <option value="sa_id" <?= ($kyc['id_type'] ?? '') === 'sa_id' ? 'selected' : '' ?>>South African ID</option>
                <option value="passport" <?= ($kyc['id_type'] ?? '') === 'passport' ? 'selected' : '' ?>>Passport</option>
            </select>
        </label>
        <label>ID number<input type="text" name="id_number" value="<?= e($kyc['id_number'] ?? '') ?>" required></label>
        <label>Upload ID/passport (PDF, JPG or PNG, max 5MB)
            <input type="file" name="id_document" accept=".pdf,.jpg,.jpeg,.png" required>
        </label>
        <button type="submit" class="btn">Submit for verification</button>
    </form>
</div>
