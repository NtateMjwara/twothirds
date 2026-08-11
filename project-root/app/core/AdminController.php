<?php
namespace app\core;

// Every admin\* controller extends this instead of Controller directly, so
// admin pages always render inside the admin layout automatically. Nothing
// admin-specific belongs here beyond that default - real admin logic still
// lives in the individual controllers and services.
abstract class AdminController extends Controller
{
    protected function render(string $view, array $data = [], string $layout = 'admin'): void
    {
        parent::render($view, $data, $layout);
    }
}
