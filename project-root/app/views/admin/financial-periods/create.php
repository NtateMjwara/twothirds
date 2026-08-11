<p><a href="/company/<?= e($company['reference']) ?>">&larr; Back to <?= e($company['name']) ?></a></p>
<div class="page-title-row">
    <h1>Record a financial period</h1>
    <span class="ref-badge"><?= e($company['reference']) ?></span>
</div>
<?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>

<div class="settings-card">
    <form method="post" action="/admin/companies/<?= e($company['reference']) ?>/financials">
        <?= csrf_field() ?>
        <label>Period start<input type="date" name="period_start" required></label>
        <label>Period end<input type="date" name="period_end" required></label>
        <label>Gross revenue<input type="number" step="0.01" name="gross_revenue" required></label>
        <label>Operating costs<input type="number" step="0.01" name="operating_costs" required></label>
        <label>Net operating income<input type="number" step="0.01" name="net_operating_income" required></label>
        <label>Asset Replacement Fund allocation<input type="number" step="0.01" name="arf_allocation" value="0"></label>
        <button type="submit" class="btn">Record period</button>
    </form>
</div>
