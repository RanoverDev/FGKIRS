<?php

namespace Core;

/**
 * Router Class - Clean URL Routing System
 * Handles HTTP routing with Front Controller Pattern
 */
class Router
{
    private array $routes = [];

    /**
     * Add a route
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $path URL path (e.g., '/users/{id}')
     * @param string|callable $handler Controller@method or callable
     */
    public function add(string $method, string $path, string|callable $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    /**
     * Convenience method for GET routes
     */
    public function get(string $path, string|callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * Convenience method for POST routes
     */
    public function post(string $path, string|callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * Dispatch the current request
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->getUri();

        // Try exact match first
        if (isset($this->routes[$method][$uri])) {
            $this->handleRoute($this->routes[$method][$uri]);
            return;
        }

        // Try dynamic routes (with parameters)
        $params = [];
        foreach ($this->routes[$method] as $route => $handler) {
            if ($this->matchRoute($route, $uri, $params)) {
                $this->handleRoute($handler, $params);
                return;
            }
        }

        // 404 Not Found
        $this->notFound();
    }

    /**
     * Get the current URI path
     */
    private function getUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Remove trailing slash except for root
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        return $uri;
    }

    /**
     * Match route pattern with URI
     * 
     * @param string $route Route pattern (e.g., '/users/{id}')
     * @param string $uri Current URI
     * @param array &$params Output parameters
     * @return bool
     */
    private function matchRoute(string $route, string $uri, array &$params = []): bool
    {
        // Convert route pattern to regex
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Extract named parameters
            $params = array_filter($matches, fn($key) => !is_numeric($key), ARRAY_FILTER_USE_KEY);
            return true;
        }

        return false;
    }

    /**
     * Handle a matched route
     * 
     * @param string|callable $handler
     * @param array $params Route parameters
     */
    private function handleRoute(string|callable $handler, array $params = []): void
    {
        // If handler is callable, execute it
        if (is_callable($handler)) {
            call_user_func_array($handler, array_values($params));
            return;
        }

        // If handler is string, parse Controller@method
        if (is_string($handler) && str_contains($handler, '@')) {
            [$controllerName, $method] = explode('@', $handler);

            // Ensure Controllers namespace is prepended if not already there
            if (!str_starts_with($controllerName, 'Controllers\\')) {
                $controllerName = 'Controllers\\' . $controllerName;
            }

            // Check if controller exists
            if (!class_exists($controllerName)) {
                $this->error500("Controller not found: {$controllerName}");
                return;
            }

            $controller = new $controllerName();

            // Check if method exists
            if (!method_exists($controller, $method)) {
                $this->error500("Method not found: {$controllerName}@{$method}");
                return;
            }

            // array_values: strip associative keys so PHP 8 does not treat them as named arguments
            call_user_func_array([$controller, $method], array_values($params));
            return;
        }

        $this->error500("Invalid route handler");
    }

    /**
     * 404 Not Found Handler
     */
    private function notFound(): void
    {
        http_response_code(404);

        // Check if custom 404 view exists
        $notFoundView = __DIR__ . '/../Views/errors/404.php';

        if (file_exists($notFoundView)) {
            require $notFoundView;
        } else {
            echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página não encontrada</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        h1 { font-size: 72px; margin: 0; color: #E11D48; }
        p { font-size: 18px; color: #666; }
        a { color: #E11D48; text-decoration: none; }
    </style>
</head>
<body>
    <h1>404</h1>
    <p>Página não encontrada</p>
    <a href="/">Voltar para home</a>
</body>
</html>';
        }
        exit;
    }

    /**
     * 500 Server Error Handler
     */
    private function error500(string $message = ''): void
    {
        http_response_code(500);

        if ($_ENV['APP_DEBUG'] ?? false) {
            echo '<h1>500 - Erro interno</h1>';
            echo '<p>' . htmlspecialchars($message) . '</p>';
        } else {
            echo '<h1>500 - Erro interno</h1>';
            echo '<p>Ocorreu um erro no servidor.</p>';
        }
        exit;
    }

    /**
     * Redirect to another URL
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: $url", true, $statusCode);
        exit;
    }
}
