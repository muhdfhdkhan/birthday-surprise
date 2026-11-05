<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;

final class Router
{
    /** @var array<int, array<string,mixed>> */
    private array $routes = [];

    /**
     * Register route rows from routes/web.php + routes/api.php.
     * Each entry: ['GET', '/path', [Controller::class, 'method'], ['auth']]
     */
    public function register(array $routes, string $group = 'web'): void
    {
        foreach ($routes as $row) {
            [$method, $path, $handler, $middleware] = $row;
            $this->routes[] = [
                'method'     => strtoupper($method),
                'path'       => '/' . trim($path, '/'),
                'handler'    => $handler,
                'middleware' => (array) $middleware,
                'group'      => $group,
            ];
        }
    }

    /** Match the request and run its middleware + handler. */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST') {
            $spoof = strtoupper((string) ($_POST['_method'] ?? ''));
            if (in_array($spoof, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                $method = $spoof;
            }
        }

        $path = $this->currentPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $params = $this->match($route['path'], $path);
            if ($params === null) {
                continue;
            }

            foreach ($route['middleware'] as $strap) {
                if (AuthMiddleware::handle($strap, $route['group'] === 'api')) {
                    return; // middleware already answered (redirect / 401)
                }
            }

            [$class, $action] = $route['handler'];
            (new $class())->{$action}(...$this->casts((array) $route, $params));
            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'Page not found']);
    }

    /** Path relative to the front-controller directory. */
    private function currentPath(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base   = rtrim(dirname($script), '/');
        if ($base === '/' || $base === '.') {
            $base = '';
        }

        $uri = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = '/' . ltrim($uri, '/');
        if ($uri !== '') {
            $uri = rtrim($uri, '/');
        }

        return $uri === '' ? '/' : $uri;
    }

    /** Exact match or simple {param} support → params array or null. */
    private function match(string $routePath, string $path): ?array
    {
        if ($routePath === $path) {
            return [];
        }
        if (!str_contains($routePath, '{')) {
            return null;
        }
        $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $routePath);
        if ($pattern === null || !preg_match('#^' . $pattern . '$#u', $path, $m)) {
            return null;
        }
        array_shift($m);
        return array_map('urldecode', $m);
    }

    /**
     * Route params arrive as strings, but controllers use typed ints and
     * strict_types. Cast every placeholder whose name ends in "id" to int
     * (only when it really is numeric).
     */
    private function casts(array $route, array $params): array
    {
        preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', (string) $route['path'], $names);
        $out = [];
        $i = 0;
        foreach ($names[1] as $name) {
            $value = $params[$i++];
            if ((preg_match('/id$/i', $name)) && ctype_digit((string) $value)) {
                $value = (int) $value;
            }
            $out[] = $value;
        }
        return $out;
    }
}
