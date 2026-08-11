<?php
namespace app\core;

class View
{
    public function render(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data);

        $viewFile = __DIR__ . "/../views/{$view}.php";
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require __DIR__ . "/../views/layouts/{$layout}.php";
    }
}
