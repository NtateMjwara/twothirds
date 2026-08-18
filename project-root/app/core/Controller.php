<?php
namespace app\core;

class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        (new View())->render($view, $data, $layout);
    }

    protected function redirect(string $path): void
    {
        header("Location: {$path}");
        exit;
    }

    protected function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    protected function requireAdmin(): void
    {
        if (empty($_SESSION['admin_id'])) {
            $this->redirect('/admin/login');
        }
    }

    // Call at the top of every POST action that writes data
    protected function verifyCsrf(): void
    {
        if (($_POST['_csrf'] ?? '') !== ($_SESSION['_csrf'] ?? '')) {
            http_response_code(419);
            exit('Invalid request.');
        }
    }

    /**
     * Queue a message to show after a redirect.
     *
     * Every admin write action ends in a redirect, which means the only way to
     * report the outcome is to carry it across the request. Without this, a
     * failed approval and a successful one look identical: the page reloads and
     * nothing is said either way.
     *
     * @param string $type success|error|warning
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /** Read and clear. Called once per request, by the layout. */
    public static function takeFlash(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }
}
