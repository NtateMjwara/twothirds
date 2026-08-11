<p><a href="/company/<?= e($company['reference']) ?>">&larr; Back to <?= e($company['name']) ?></a></p>
<div class="page-title-row">
    <h1>Upload a document</h1>
    <span class="ref-badge"><?= e($company['reference']) ?></span>
</div>
<?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>

<div class="settings-card">
    <form method="post" action="/admin/companies/<?= e($company['reference']) ?>/documents" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>Document type
            <select name="doc_type">
                <option value="financial_statements">Financial statements</option>
                <option value="valuation_certificate">Valuation certificate</option>
                <option value="moi">Memorandum of incorporation</option>
                <option value="vehicle_registration">Vehicle registration</option>
            </select>
        </label>
        <label>File (PDF, JPG or PNG, max 10MB)
            <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
        </label>
        <button type="submit" class="btn">Upload</button>
    </form>
</div>
