<?php

declare(strict_types=1);

/**
 * OmniMesh IoT & Telemetry Orchestrator
 * Front Controller & Application Bootstrapper
 *
 * PHP 8.3 OOP MVC Architecture
 */

// If running with PHP's built-in web server and a static file exists, serve it directly
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if ($path !== null && $path !== '/' && file_exists(__DIR__ . $path) && is_file(__DIR__ . $path)) {
        return false;
    }
}

// Production-ready error handling
error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('UTC');

// Define base root path
define('BASE_PATH', dirname(__DIR__));

// PSR-4 Style Autoloader for App namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Bootstrap Database & Migration Runner
try {
    \App\Core\Database::getInstance();
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>OmniMesh - Database Error</title></head><body style='font-family:sans-serif;background:#0b0f19;color:#ff5555;padding:2rem;'>";
    echo "<h1>OmniMesh Database Initialization Failed</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
    echo "</body></html>";
    exit;
}

// Instantiate Router & Load Route Definitions
$router = new \App\Core\Router();
require BASE_PATH . '/routes/web.php';

// Dispatch incoming request
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

$router->dispatch($method, $uri);
