<?php
/**
 * Shown when a signed-in admin reaches something their role doesn't cover.
 *
 * Deliberately a 403 and not a redirect to the login page: they are logged in,
 * and bouncing them to a login form suggests signing in again would help. It
 * wouldn't.
 */
use app\services\AdminPolicy;
$role = $_SESSION['admin_role'] ?? null;
?>
<div class="empty-state" style="max-width:560px; margin:3rem auto;">
    <div class="asset-icon"><i class="ti ti-lock" aria-hidden="true"></i></div>
    <h1 style="font-size:1.4rem;">Not available to your role</h1>
    <p class="muted">
        You're signed in as <strong><?= e(AdminPolicy::label($role)) ?></strong>, which doesn't
        cover this page. If you need it, ask a super admin to change your role rather than
        signing in as someone else.
    </p>
    <p class="muted" style="font-size:0.8rem;">Required permission: <code><?= e($permission ?? 'unknown') ?></code></p>
    <p><a href="/admin" class="btn"><i class="ti ti-arrow-left" aria-hidden="true"></i> Back to the dashboard</a></p>
</div>
