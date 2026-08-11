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
}
