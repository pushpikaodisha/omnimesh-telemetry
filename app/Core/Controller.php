<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Base Controller providing view rendering, JSON response helpers, and request body parsing.
 */
abstract class Controller
{
    /**
     * Renders a view wrapped inside a master layout template.
     *
     * @param string $view Relative view name (e.g. 'dashboard', 'devices', 'rules')
     * @param array<string, mixed> $data Variables passed to the view and layout
     * @param string $layout Layout template filename
     */
    protected function view(string $view, array $data = [], string $layout = 'layout'): void
    {
        $viewsDir = dirname(__DIR__, 2) . '/views';
        $viewFile = "{$viewsDir}/{$view}.php";
        $layoutFile = "{$viewsDir}/{$layout}.php";

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View file not found: {$viewFile}");
        }

        // Extract variables to view scope
        extract($data);

        // Start output buffering to capture inner view HTML
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // If a layout is specified, render the layout with $content
        if ($layout !== '' && file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Sends a JSON response with status code.
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Parses JSON payload from php://input or falls back to $_POST.
     *
     * @return array<string, mixed>
     */
    protected function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw !== false && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $_POST;
    }

    /**
     * Redirects the browser to another URI.
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }
}
