<p><a href="/company/<?= e($company['reference']) ?>">&larr; Back to <?= e($company['name']) ?></a></p>
<div class="page-title-row">
    <h1>Company updates</h1>
    <span class="ref-badge"><?= e($company['reference']) ?></span>
</div>
<?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>

<div class="settings-card" style="max-width:640px;">
    <h2 style="margin-top:0;">Add an update</h2>
    <p class="muted" style="font-size:0.88rem;">
        These form the timeline on the public company page. Record things that actually
        happened and are checkable &mdash; the offer opening, the asset being delivered or
        licensed, an operator appointed, a period filed. Not marketing lines.
    </p>

    <form method="post" action="/admin/companies/<?= e($company['reference']) ?>/updates">
        <?= csrf_field() ?>
        <label>Title<input type="text" name="title" maxlength="160" required placeholder="Asset delivered and registered"></label>
        <label>Date<input type="date" name="happened_on" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></label>
        <label>Detail (optional)<textarea name="body" rows="3" placeholder="One or two sentences of context."></textarea></label>
        <button type="submit" class="btn">Add update</button>
    </form>
</div>

<h2>Timeline (<?= count($updates) ?>)</h2>

<?php if (!$updates): ?>
    <p class="muted">Nothing recorded yet. The public page explains the timeline is empty rather than hiding the section.</p>
<?php else: ?>
    <table>
        <tr><th>Date</th><th>Title</th><th>Detail</th><th></th></tr>
        <?php foreach ($updates as $u): ?>
        <tr>
            <td><?= e(date('j M Y', strtotime($u['happened_on']))) ?></td>
            <td><?= e($u['title']) ?></td>
            <td class="muted"><?= e($u['body'] ? mb_strimwidth($u['body'], 0, 90, '...') : '') ?></td>
            <td class="admin-actions">
                <form method="post" action="/admin/companies/<?= e($company['reference']) ?>/updates/<?= (int) $u['id'] ?>/delete"
                      onsubmit="return confirm('Delete this update?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-compact btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
