<?php

namespace Controllers;

/**
 * Base Controller
 * All controllers should extend this class
 */
abstract class Controller
{
    /**
     * Load a view file with Pro-level path resolution
     * 
     * @param string $name View name (e.g., 'home/index')
     * @param array $data Data to pass to the view
     */
    protected function view(string $name, array $data = []): void
    {
        extract($data);

        // 1. Find the 'Views' directory case-insensitively
        $appPath = dirname(__DIR__);
        $viewsDir = $this->findCaseInsensitive($appPath, 'Views');

        if (!$viewsDir) {
            http_response_code(500);
            die("<strong>Erro de Sistema</strong><br>Pasta de visualizações (Views) não encontrada em: {$appPath}");
        }

        $basePath = $appPath . DIRECTORY_SEPARATOR . $viewsDir . DIRECTORY_SEPARATOR;

        // 2. Normalize and resolve the view file
        $name = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $name);
        $viewPath = $basePath . $name . '.php';

        if (!file_exists($viewPath)) {
            $viewPath = $this->resolveViewPath($basePath, $name);
        }

        // Final check
        if (!$viewPath || !file_exists($viewPath)) {
            http_response_code(404);
            die("<strong>404 - Página não encontrada</strong><br>A visualização solicitada não existe.");
        }

        require $viewPath;
    }

    /**
     * Finds a file or directory case-insensitively
     */
    private function findCaseInsensitive(string $path, string $target): ?string
    {
        if (!is_dir($path))
            return null;
        $items = scandir($path);
        foreach ($items as $item) {
            if (strtolower($item) === strtolower($target)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Expert Resolver: Searches for the view file ignoring case differences
     */
    private function resolveViewPath(string $basePath, string $name): ?string
    {
        $parts = explode(DIRECTORY_SEPARATOR, $name);
        $currentPath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $resolvedParts = [];

        foreach ($parts as $part) {
            $foundItem = $this->findCaseInsensitive($currentPath, $part);

            if (!$foundItem) {
                // Try with .php extension
                $foundItem = $this->findCaseInsensitive($currentPath, $part . '.php');
            }

            if ($foundItem) {
                $currentPath .= DIRECTORY_SEPARATOR . $foundItem;
            } else {
                return null;
            }
        }

        return str_ends_with($currentPath, '.php') ? $currentPath : $currentPath . '.php';
    }

    /**
     * Return JSON response
     * 
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to another URL
     * 
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code (default 302)
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: $url", true, $statusCode);
        exit;
    }

    /**
     * Get POST data
     * 
     * @param string|null $key Specific key or null for all data
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    protected function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_POST;
        }

        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data
     * 
     * @param string|null $key Specific key or null for all data
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    protected function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
    }
}
