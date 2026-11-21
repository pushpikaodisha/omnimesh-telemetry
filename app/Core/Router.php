<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Clean Regex Route Dispatcher supporting GET, POST, and SSE Streaming.
 */
class Router
{
    /** @var array<array{method: string, pattern: string, regex: string, handler: mixed}> */
    private array $routes = [];

    public function get(string $path, mixed $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, mixed $handler): void
    {
        // Convert route placeholders like {id} into named regex groups
        $pattern = '/' . trim($path, '/');
        if ($pattern === '') {
            $pattern = '/';
        }

        // Replace {paramName} with regex capture group
        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $regex,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        // Extract clean path from URI (strip query string)
        $cleanPath = parse_url($uri, PHP_URL_PATH) ?? '/';
        $cleanPath = '/' . trim($cleanPath, '/');
        if ($cleanPath === '') {
            $cleanPath = '/';
        }

        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $cleanPath, $matches)) {
                // Filter out numerical indices from regex matches, keep named parameters
                $params = array_filter($matches, function ($key) {
                    return !is_numeric($key);
                }, ARRAY_FILTER_USE_KEY);

                $this->executeHandler($route['handler'], $params);
                return;
            }
        }

        // 404 Not Found handling
        $this->handleNotFound($cleanPath);
    }

    private function executeHandler(mixed $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;

            if (is_string($class)) {
                $class = new $class();
            }

            if (method_exists($class, $method)) {
                call_user_func_array([$class, $method], $params);
                return;
            }
        }

        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid route handler execution definition',
        ], JSON_PRETTY_PRINT);
    }

    private function handleNotFound(string $path): void
    {
        http_response_code(404);

        if (str_starts_with($path, '/api/')) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => "Resource or API route '{$path}' not found",
            ], JSON_PRETTY_PRINT);
            return;
        }

        // Render clean HTML 404 view
        $viewPath = dirname(__DIR__, 2) . '/views/layout.php';
        $title = "404 Not Found - OmniMesh Orchestrator";
        $activeTab = '404';
        $content = "
            <div class=\"glass-card error-container\" style=\"text-align:center; padding: 4rem 2rem; margin: 2rem auto; max-width: 600px;\">
                <div style=\"font-size: 4rem; color: var(--accent-cyan); font-family: var(--font-mono); font-weight: bold; margin-bottom: 1rem;\">404</div>
                <h2 style=\"margin-bottom: 1rem; color: #fff;\">Telemetry Link Severed</h2>
                <p style=\"color: var(--text-dim); margin-bottom: 2rem;\">The requested node or page route <code>" . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . "</code> does not exist on the OmniMesh grid.</p>
                <a href=\"/\" class=\"btn btn-primary\" style=\"display: inline-flex; align-items: center; gap: 0.5rem;\">
                    <svg width=\"18\" height=\"18\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" viewBox=\"0 0 24 24\"><path d=\"M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z\"></path><polyline points=\"9 22 9 12 15 12 15 22\"></polyline></svg>
                    Return to Telemetry Deck
                </a>
            </div>
        ";

        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "<h1>404 Not Found</h1><p>The requested route was not found.</p>";
        }
    }
}
