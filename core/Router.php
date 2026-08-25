<?php
// Minimal front-controller router: maps "module/action/param" URLs to controller methods.

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    private function normalize(string $path): string
    {
        return '/' . trim($path, '/');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalize(parse_url($uri, PHP_URL_PATH));
        // Strip the public base path so routes work regardless of sub-folder depth.
        $base = rtrim(BASE_URL, '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
            $path = $path === '' ? '/' : $path;
        }

        $handlers = $this->routes[$method] ?? [];

        if (isset($handlers[$path])) {
            call_user_func($handlers[$path]);
            return;
        }

        // Support simple params: /module/action/{id}
        foreach ($handlers as $routePath => $handler) {
            $pattern = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $routePath);
            if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
                array_shift($matches);
                call_user_func_array($handler, $matches);
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }
}
