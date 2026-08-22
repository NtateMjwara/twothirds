<?php
/**
 * A legal document.
 *
 * Plain and readable on purpose. Someone reaching this page is being asked to
 * agree to something, and the page should not be competing for their attention
 * with anything else.
 */
$isPlaceholder = str_contains((string) $document['body'], '[PLACEHOLDER');
?>
<div class="legal-layout">
    <nav class="legal-nav" aria-label="Documents">
        <p class="legal-nav-title">Documents</p>
        <ul>
            <?php foreach ($related as $doc): ?>
                <li>
                    <a href="/legal/<?= e($doc['doc_key']) ?><?= $company ? '?company=' . e($company['reference']) : '' ?>"
                       class="<?= $doc['doc_key'] === $document['doc_key'] ? 'is-current' : '' ?>">
                        <?= e($doc['title']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <article class="legal-doc">
        <header class="legal-head">
            <h1><?= e($document['title']) ?></h1>
            <p class="legal-meta muted">
                Version <?= e($document['version']) ?>
                <?php if (!empty($document['effective_from'])): ?>
                    &middot; effective <?= e(date('j F Y', strtotime($document['effective_from']))) ?>
                <?php endif; ?>
                <?php if ($company): ?>
                    &middot; <?= e($company['name']) ?>
                <?php else: ?>
                    &middot; applies platform-wide
                <?php endif; ?>
            </p>
        </header>

        <?php if ($isPlaceholder): ?>
            <!-- Stated loudly and deliberately. A placeholder that reads like a
                 real instrument is worse than no document at all. -->
            <div class="callout callout-warn">
                <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                <p>
                    <strong>This is a placeholder, not a legal document.</strong>
                    It describes what the final instrument will cover so the platform can be
                    built and reviewed. It has no legal effect and must be replaced with drafted,
                    reviewed text before it governs a real transaction.
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($document['file_path'])): ?>
            <p><a class="btn-outline" href="/documents/<?= e($document['file_path']) ?>" target="_blank" rel="noopener">
                <i class="ti ti-download" aria-hidden="true"></i> Download the signed version
            </a></p>
        <?php endif; ?>

        <div class="legal-body">
            <?= nl2br(e((string) $document['body'])) ?>
        </div>
    </article>
</div>
