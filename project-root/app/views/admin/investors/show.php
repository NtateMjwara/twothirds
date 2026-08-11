<p><a href="/admin/investors">&larr; Back to investors</a></p>
<h1><?= e(trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''))) ?: e($user['email']) ?></h1>
<p class="muted">
    <?= e($user['email']) ?> &middot;
    account <span class="status-badge status-<?= e($user['status']) ?>"><?= e($user['status']) ?></span> &middot;
    email <?= $user['email_verified_at'] ? 'verified' : 'not verified' ?>
</p>

<h2>KYC</h2>
<div class="settings-card" style="padding:1.25rem 1.75rem;">
    <?php if ($kyc): ?>
        <p style="margin:0 0 8px;">
            Status: <span class="status-badge status-<?= e($kyc['status']) ?>"><?= e($kyc['status']) ?></span>
            <span class="muted">(<?= e($kyc['id_type']) ?> <?= e($kyc['id_number']) ?>)</span>
        </p>
        <?php if ($kyc['status'] === 'rejected' && !empty($kyc['rejection_reason'])): ?>
            <p class="muted" style="margin:0 0 8px;">Reason: <?= e($kyc['rejection_reason']) ?></p>
        <?php endif; ?>
        <?php if ($kyc['document_id']): ?>
            <p style="margin:0;"><a href="/documents/<?= (int) $kyc['document_id'] ?>">View submitted document</a></p>
        <?php endif; ?>
    <?php else: ?>
        <p class="muted" style="margin:0;">No KYC submitted yet.</p>
    <?php endif; ?>
</div>

<h2>Bank account</h2>
<div class="settings-card" style="padding:1rem 1.75rem;">
    <p class="muted" style="margin:0;"><?= $bankAccount ? 'On file (' . e($bankAccount['bank_name']) . ')' : 'Not on file' ?></p>
</div>

<h2>Registered holdings</h2>
<?php if (empty($holdings)): ?>
    <p class="muted">None.</p>
<?php else: ?>
<table>
    <tr><th>Company</th><th>Shares</th><th>Value</th><th>Settled</th></tr>
    <?php foreach ($holdings as $h): ?>
    <tr>
        <td><a href="/company/<?= e($h['company_reference']) ?>"><?= e($h['company_name']) ?></a></td>
        <td><?= number_format((int) $h['shares']) ?></td>
        <td>R<?= number_format($h['shares'] * $h['nav_per_share'], 2) ?></td>
        <td><?= e($h['settled_at']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<h2>Pending commitments</h2>
<?php if (empty($commitments)): ?>
    <p class="muted">None.</p>
<?php else: ?>
<table>
    <tr><th>Reference</th><th>Company</th><th>Shares</th><th>Expires</th></tr>
    <?php foreach ($commitments as $c): ?>
    <tr>
        <td><span class="ref-badge"><?= e($c['reference']) ?></span></td>
        <td><?= e($c['company_name']) ?></td>
        <td><?= number_format((int) $c['shares_requested']) ?></td>
        <td><?= e($c['expires_at']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
