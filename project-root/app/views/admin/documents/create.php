<?php $currentTab = 'documents'; require __DIR__ . '/../partials/_company-tabs.php'; ?>
<?php if (!empty($error)): ?><p class="form-error"><i class="ti ti-alert-circle" aria-hidden="true"></i> <?= e($error) ?></p><?php endif; ?>

<div class="settings-card" style="max-width:600px;">
    <h2 style="margin-top:0;">Upload a document</h2>
    <p class="muted" style="font-size:0.86rem;">
        PDF, JPG or PNG, up to 10MB. Everything uploaded here is published on the public
        company page &mdash; that's the point of it &mdash; so check for personal details
        before uploading anything an operator or director sent you.
    </p>

    <form method="post" action="/admin/companies/<?= e($company['reference']) ?>/documents" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>Document type
            <select name="doc_type" required>
                <?php foreach ($docTypes as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>File<input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required></label>
        <button type="submit" class="btn"><i class="ti ti-upload" aria-hidden="true"></i> Upload</button>
    </form>
</div>

<h2>On this company (<?= count($documents) ?>)</h2>
<?php if (empty($documents)): ?>
    <p class="muted">Nothing uploaded yet. The public page shows an empty documents tab.</p>
<?php else: ?>
<div class="table-scroll">
    <table class="admin-table">
        <thead><tr><th scope="col">Type</th><th scope="col">Uploaded</th><th scope="col">Verified</th><th scope="col"></th></tr></thead>
        <tbody>
        <?php foreach ($documents as $d): ?>
            <tr>
                <td><?= e($docTypes[$d['doc_type']] ?? ucwords(str_replace('_', ' ', $d['doc_type']))) ?></td>
                <td><?= e(date('j M Y, H:i', strtotime($d['uploaded_at'] ?? $d['created_at'] ?? 'now'))) ?></td>
                <td><?= $d['verified'] ? '<span class="status-badge status-verified">Verified</span>' : '<span class="muted">No</span>' ?></td>
                <td><a href="/documents/<?= (int) $d['id'] ?>" target="_blank" rel="noopener">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
