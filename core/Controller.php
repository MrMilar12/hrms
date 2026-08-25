<?php
// Base controller: shared helpers for view rendering, JSON responses, and CSRF.

abstract class Controller
{
    protected function view(string $module, string $view, array $data = []): void
    {
        extract($data);
        $viewFile = MODULES_PATH . "/{$module}/views/{$view}.php";
        if (!is_file($viewFile)) {
            throw new RuntimeException("View not found: {$viewFile}");
        }
        require $viewFile;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function requireCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Auth::verifyCsrfToken($token)) {
            $this->json(['error' => 'Invalid CSRF token.'], 419);
        }
    }
}
