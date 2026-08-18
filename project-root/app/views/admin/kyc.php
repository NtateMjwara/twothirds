<?php
/**
 * KYC review queue.
 *
 * ID numbers are masked. The reviewer's job is to compare the uploaded document
 * to the submitted details, which needs the document open and the last few
 * digits to match against - not a table of full identity numbers sitting on
 * screen for everyone in the queue at once.
 */
$maskId = static function (string $number): string {
    $length = strlen($number);
    if ($length <= 4) {
        return str_repeat('•', $length);
    }
    return str_repeat('•', $length - 4) . substr($number, -4);
};
?>
<div class="page-title-row">
    <h1>KYC review</h1>
    <?php if (!empty($queue)): ?>
        <span class="ref-badge"><?= count($queue) ?> pending</span>
    <?php endif; ?>
</div>

<?php if (empty($queue)): ?>
    <div class="empty-state">
        <div class="asset-icon"><i class="ti ti-shield-check" aria-hidden="true"></i></div>
        <h3>Nothing waiting</h3>
        <p class="muted">Every submitted identity document has been reviewed.</p>
    </div>
<?php else: ?>

<?php if (!$canAct): ?>
    <p class="notice notice-info">
        <i class="ti ti-eye" aria-hidden="true"></i>
        Your role can see this queue but not decide on it.
    </p>
<?php endif; ?>

<div class="review-list">
    <?php foreach ($queue as $k): ?>
        <?php $isRevealed = (int) $k['id'] === (int) $revealed; ?>
        <article class="review-card">
            <div class="review-head">
                <div>
                    <h2 class="review-name">
                        <?= e(trim(($k['first_name'] ?? '') . ' ' . ($k['last_name'] ?? ''))) ?: 'Name not on file' ?>
                    </h2>
                    <p class="muted review-email"><?= e($k['user_email']) ?></p>
                </div>
                <span class="status-badge status-pending">Pending</span>
            </div>

            <dl class="review-facts">
                <div>
                    <dt>ID type</dt>
                    <dd><?= $k['id_type'] === 'passport' ? 'Passport' : 'South African ID' ?></dd>
                </div>
                <div>
                    <dt>ID number</dt>
                    <dd class="is-mono">
                        <?php if ($isRevealed): ?>
                            <?= e($k['id_number']) ?>
                            <span class="revealed-flag"><i class="ti ti-eye" aria-hidden="true"></i> shown &amp; logged</span>
                        <?php else: ?>
                            <?= e($maskId((string) $k['id_number'])) ?>
                            <?php if ($canReveal): ?>
                                <form method="post" action="/admin/kyc/<?= (int) $k['id'] ?>/reveal" class="reveal-form">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn-compact">Reveal</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt>Submitted</dt>
                    <dd><?= e(date('j M Y, H:i', strtotime($k['updated_at']))) ?></dd>
                </div>
            </dl>

            <?php if ($k['document_id']): ?>
                <p class="review-doc">
                    <a href="/documents/<?= (int) $k['document_id'] ?>" target="_blank" rel="noopener" class="btn-outline">
                        <i class="ti ti-file-search" aria-hidden="true"></i> Open the uploaded document
                    </a>
                </p>
            <?php else: ?>
                <p class="notice notice-warn">
                    <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                    No document attached to this submission. There is nothing to verify against
                    &mdash; reject it and ask them to upload again.
                </p>
            <?php endif; ?>

            <?php if ($canAct): ?>
            <div class="review-actions">
                <form method="post" action="/admin/kyc/<?= (int) $k['id'] ?>/approve"
                      onsubmit="return confirm('Approve this investor\'s identity verification? They will be notified immediately.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-compact btn-positive">
                        <i class="ti ti-check" aria-hidden="true"></i> Approve
                    </button>
                </form>

                <form method="post" action="/admin/kyc/<?= (int) $k['id'] ?>/reject" class="reject-form">
                    <?= csrf_field() ?>
                    <label class="sr-only" for="reason-<?= (int) $k['id'] ?>">Reason for rejection</label>
                    <input type="text" id="reason-<?= (int) $k['id'] ?>" name="reason" maxlength="255" required
                           placeholder="Reason shown to the investor, e.g. document is unreadable">
                    <button type="submit" class="btn-compact btn-negative">
                        <i class="ti ti-x" aria-hidden="true"></i> Reject
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>
