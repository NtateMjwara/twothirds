<p><a href="/company/<?= e($company['reference']) ?>">&larr; Back to <?= e($company['name']) ?></a></p>

<div class="narrow-card">
    <h1 style="font-size:1.3rem; margin-bottom:0.25rem;">Commit to invest</h1>
    <p class="muted" style="margin-top:0; margin-bottom:1.25rem;">
        <?= e($company['name']) ?> <span class="ref-badge"><?= e($company['reference']) ?></span>
    </p>

    <div class="stat-row" style="margin:0 0 1.25rem;">
        <div class="stat">
            <span class="stat-value"><?= number_format($available) ?></span>
            <span class="stat-label">Available</span>
        </div>
    </div>

    <?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="/commit/<?= e($company['reference']) ?>">
        <?= csrf_field() ?>
        <label>Shares to commit
            <input type="number" name="shares" min="1" max="<?= (int) $available ?>" required>
        </label>
        <p class="muted" style="font-size:0.82rem;">No payment is taken here. Settlement is arranged directly and confirmed by the platform.</p>
        <button type="submit" class="btn">Submit commitment</button>
    </form>
</div>
