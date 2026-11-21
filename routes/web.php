<?php

declare(strict_types=1);

/**
 * OmniMesh Telemetry Orchestrator Routing Table
 *
 * @var App\Core\Router $router
 */

use App\Controllers\DeviceController;
use App\Controllers\TelemetryController;
use App\Controllers\RuleController;

// Web UI Page Routes
$router->get('/', [TelemetryController::class, 'dashboard']);
$router->get('/devices', [DeviceController::class, 'index']);
$router->get('/rules', [RuleController::class, 'index']);

// Real-Time Server-Sent Events (SSE) Route
$router->get('/api/stream', [TelemetryController::class, 'sseStream']);

// Telemetry & Physics Simulation APIs
$router->get('/api/telemetry/simulate-step', [TelemetryController::class, 'apiSimulateStep']);
$router->post('/api/telemetry/ingest', [TelemetryController::class, 'apiIngest']);
$router->get('/api/telemetry/history', [TelemetryController::class, 'apiHistory']);
$router->get('/api/telemetry/stats', [TelemetryController::class, 'apiStats']);
$router->get('/api/telemetry/incidents', [TelemetryController::class, 'apiIncidents']);

// Device Management APIs
$router->get('/api/devices', [DeviceController::class, 'apiList']);
$router->post('/api/devices', [DeviceController::class, 'apiCreate']);
$router->post('/api/devices/{id}/toggle', [DeviceController::class, 'apiToggle']);
$router->post('/api/devices/{id}/update', [DeviceController::class, 'apiUpdate']);
$router->post('/api/devices/{id}/delete', [DeviceController::class, 'apiDelete']);

// Automation Rule Engine APIs
$router->get('/api/rules', [RuleController::class, 'apiList']);
$router->post('/api/rules', [RuleController::class, 'apiCreate']);
$router->post('/api/rules/{id}/toggle', [RuleController::class, 'apiToggle']);
$router->post('/api/rules/{id}/delete', [RuleController::class, 'apiDelete']);
$router->get('/api/rules/incidents', [RuleController::class, 'apiTriggers']);
