<div class="narrow-card">
    <?php if (!empty($verified)): ?>
        <div class="status-icon">&check;</div>
        <h1 style="text-align:center;">Email verified</h1>
        <p class="muted" style="text-align:center;">Your email address has been verified.</p>
        <p style="text-align:center; margin-top:1.25rem;"><a href="/account/portfolio" class="btn">Go to your portfolio</a></p>
    <?php elseif (!empty($invalid)): ?>
        <h1>Link invalid or expired</h1>
        <p class="muted">This verification link is invalid or has expired.</p>
        <p><a href="/login">Log in</a> to request a new one.</p>
    <?php endif; ?>
</div>
