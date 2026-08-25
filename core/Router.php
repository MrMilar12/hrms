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
        // URL paths on this Windows/XAMPP installation may arrive with a
        // different directory-name casing (for example /hrms vs /HRMS).
        // Match the configured base path case-insensitively, while leaving
        // the application route itself unchanged.
        if ($base !== '' && strncasecmp($path, $base, strlen($base)) === 0) {
            $path = substr($path, strlen($base));
            $path = $path === '' ? '/' : $path;
        }

        $setupRoutes = ['/personnel-setup', '/logout'];
        if (Auth::check() && Auth::needsPersonnelSetup() && !in_array($path, $setupRoutes, true)) {
            header('Location: ' . BASE_URL . '/personnel-setup');
            exit;
        }

        $personalDetailsRoutes = ['/personal-details-setup', '/logout'];
        if (Auth::check() && !Auth::needsPersonnelSetup() && Auth::needsPersonalDetailsSetup() && !in_array($path, $personalDetailsRoutes, true)) {
            header('Location: ' . BASE_URL . '/personal-details-setup');
            exit;
        }

        if (!$this->withinRateLimit($method, $path)) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Too many requests. Please wait and try again.']);
            return;
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
                preg_match_all('#\{([a-zA-Z_]+)\}#', $routePath, $parameterNames);
                foreach ($parameterNames[1] as $index => $name) {
                    if (in_array($name, ['id', 'aid', 'atid'], true)) {
                        $decoded = UrlId::decode((string) ($matches[$index] ?? ''));
                        if ($decoded === null) {
                            http_response_code(404);
                            echo '404 Not Found';
                            return;
                        }
                        $matches[$index] = (string) $decoded;
                    }
                }
                call_user_func_array($handler, $matches);
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }

    private function withinRateLimit(string $method, string $path): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user = Auth::userId() ? 'user:' . Auth::userId() : 'ip:' . $ip;

        if ($method === 'POST' && $path === '/login') {
            $username = strtolower(trim((string) ($_POST['username'] ?? 'unknown')));
            return RateLimiter::allow('login:ip:' . $ip, 20, 900)
                && RateLimiter::allow('login:account:' . hash('sha256', $username), 5, 900);
        }
        if ($method === 'POST' && $path === '/login/verify-2fa') {
            return RateLimiter::allow('2fa:' . $ip . ':' . ($_SESSION['pending_2fa_user_id'] ?? 'unknown'), 8, 300);
        }
        if ($method === 'GET' && $path === '/search') {
            return RateLimiter::allow('search:' . $user, 120, 60);
        }
        if ($method === 'POST' && (str_contains($path, '/upload-attachment') || $path === '/profile/photo' || preg_match('#^/employees/[^/]+/photo$#', $path))) {
            return RateLimiter::allow('upload:' . $user, 20, 600);
        }
        if ($method === 'POST') {
            return RateLimiter::allow('write:' . $user, 180, 60);
        }
        return true;
    }
}
