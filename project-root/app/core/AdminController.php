<?php
namespace app\core;

use app\services\AdminPolicy;

/**
 * Every admin\* controller extends this, so admin pages render inside the admin
 * layout automatically and every action has the permission gate to hand.
 */
abstract class AdminController extends Controller
{
    protected function render(string $view, array $data = [], string $layout = 'admin'): void
    {
        parent::render($view, $data, $layout);
    }

    protected function adminRole(): ?string
    {
        return $_SESSION['admin_role'] ?? null;
    }

    protected function adminId(): int
    {
        return (int) ($_SESSION['admin_id'] ?? 0);
    }

    protected function can(string $permission): bool
    {
        return AdminPolicy::can($this->adminRole(), $permission);
    }

    /**
     * Signed in AND allowed. Replaces the bare requireAdmin() at the top of
     * every action - that only ever checked that someone was logged in, which
     * is why an ops account could settle commitments.
     *
     * Not signed in redirects to the login page; signed in but not permitted
     * gets a 403. Those are different situations and logging back in doesn't
     * fix the second one.
     */
    protected function requirePermission(string $permission): void
    {
        $this->requireAdmin();

        if (!$this->can($permission)) {
            http_response_code(403);
            $this->render('admin/forbidden', ['permission' => $permission]);
            exit;
        }
    }

    /**
     * Writes an audit entry. Used by actions that change something an investor
     * would care about.
     */
    protected function audit(string $action, string $entityType, int $entityId, string $details): void
    {
        Database::connection()->prepare(
            "INSERT INTO audit_log (actor_type, actor_id, action, entity_type, entity_id, details)
             VALUES ('admin', ?, ?, ?, ?, ?)"
        )->execute([$this->adminId(), $action, $entityType, $entityId, $details]);
    }
}
