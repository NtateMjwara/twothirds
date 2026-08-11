<p><a href="/company/<?= e($company['reference']) ?>">&larr; Back to <?= e($company['name']) ?></a></p>
<div class="page-title-row">
    <h1>Directors</h1>
    <span class="ref-badge"><?= e($company['reference']) ?></span>
</div>
<?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>

<table>
    <tr><th>Name</th><th>Role</th><th>Appointed</th><th>Status</th><th></th></tr>
    <?php foreach ($directors as $d): ?>
    <tr>
        <td><?= e($d['full_name']) ?></td>
        <td><?= e($d['role']) ?></td>
        <td><?= e($d['appointed_date']) ?></td>
        <td><span class="status-badge status-<?= e($d['status']) ?>"><?= e($d['status']) ?></span></td>
        <td class="admin-actions">
            <?php if ($d['status'] === 'active'): ?>
            <form method="post" action="/admin/companies/<?= e($company['reference']) ?>/directors/<?= (int) $d['id'] ?>/resign">
                <?= csrf_field() ?>
                <button type="submit" class="btn-compact btn-negative">Mark resigned</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<h2>Add a director</h2>
<div class="settings-card">
    <form method="post" action="/admin/companies/<?= e($company['reference']) ?>/directors">
        <?= csrf_field() ?>
        <label>Full name<input type="text" name="full_name" required></label>
        <label>Role
            <select name="role">
                <option value="Director">Director</option>
                <option value="Chairperson">Chairperson</option>
            </select>
        </label>
        <label>Appointed date<input type="date" name="appointed_date" value="<?= date('Y-m-d') ?>"></label>
        <button type="submit" class="btn">Add director</button>
    </form>
</div>
