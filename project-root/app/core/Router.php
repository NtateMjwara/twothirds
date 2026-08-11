<?php
namespace app\core;

class Router
{
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, string $handler): void
    {
        // Turns '/company/{id}' into a regex capturing the {id} segment
        $pattern = preg_replace('#\{[a-zA-Z]+\}#', '([^/]+)', trim($path, '/'));
        $this->routes[] = [$method, "#^{$pattern}$#", $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = trim(parse_url($uri, PHP_URL_PATH), '/');

        foreach ($this->routes as [$routeMethod, $pattern, $handler]) {
            if ($method === $routeMethod && preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                [$controllerName, $action] = explode('@', $handler);
                $class = "app\\controllers\\{$controllerName}";
                (new $class())->$action(...$matches);
                return;
            }
        }

        http_response_code(404);
        (new Controller())->render('errors/404');
    }
}
