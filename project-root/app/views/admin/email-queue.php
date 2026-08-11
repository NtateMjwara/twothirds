<h1>Email queue</h1>
<p class="muted">Showing pending and failed sends only - successfully delivered emails aren't listed here.</p>
<?php if (empty($queue)): ?>
    <p class="muted">Nothing pending or failed.</p>
<?php else: ?>
<table>
    <tr><th>To</th><th>Subject</th><th>Status</th><th>Attempts</th><th>Last attempt</th></tr>
    <?php foreach ($queue as $q): ?>
    <tr>
        <td><?= e($q['to_email']) ?></td>
        <td><?= e($q['subject']) ?></td>
        <td><span class="status-badge status-<?= e($q['status']) ?>"><?= e($q['status']) ?></span></td>
        <td><?= (int) $q['attempts'] ?></td>
        <td><?= e($q['last_attempted_at']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
