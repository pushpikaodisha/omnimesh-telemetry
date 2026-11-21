<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Device;
use App\Models\TelemetryLog;
use App\Models\Rule;
use App\Services\SimulationService;

/**
 * Controller handling Telemetry ingestion, historical metrics, and SSE streaming.
 */
class TelemetryController extends Controller
{
    private TelemetryLog $telemetryModel;
    private Device $deviceModel;
    private Rule $ruleModel;
    private SimulationService $simulationService;

    public function __construct()
    {
        $this->telemetryModel = new TelemetryLog();
        $this->deviceModel = new Device();
        $this->ruleModel = new Rule();
        $this->simulationService = new SimulationService();
    }

    /**
     * Dashboard View: Master Live Telemetry Deck.
     */
    public function dashboard(): void
    {
        $snapshot = $this->telemetryModel->getLatestSnapshot();
        $devices = $this->deviceModel->all();
        $incidents = $this->telemetryModel->getRecentIncidents(15);
        $stats = $this->deviceModel->getFleetStats();
        $aggregates = $this->telemetryModel->getAggregates();

        // Initial historical datasets for multi-metric chart
        $chartSeries = [
            'temperature' => $this->telemetryModel->getTimeSeries('temperature', 20),
            'pressure' => $this->telemetryModel->getTimeSeries('pressure', 20),
            'radiation' => $this->telemetryModel->getTimeSeries('radiation', 20),
            'humidity' => $this->telemetryModel->getTimeSeries('humidity', 20),
        ];

        $this->view('dashboard', [
            'title' => 'Mission Control - OmniMesh Orchestrator',
            'activeTab' => 'dashboard',
            'snapshot' => $snapshot,
            'devices' => $devices,
            'incidents' => $incidents,
            'stats' => $stats,
            'aggregates' => $aggregates,
            'chartSeries' => $chartSeries,
        ]);
    }

    /**
     * Server-Sent Events (SSE) Live Telemetry Stream.
     * Continuously simulates physics, evaluates rules, and pushes updates to connected clients.
     */
    public function sseStream(): void
    {
        // Disable script execution time limit for long-lived SSE connection
        set_time_limit(0);
        ignore_user_abort(true);

        // SSE Response Headers
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Clean any active output buffers
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        // Send initial connection handshake
        echo ": omnimesh sse stream connected\n\n";
        echo "event: handshake\n";
        echo "data: " . json_encode(['status' => 'connected', 'timestamp' => date('Y-m-d H:i:s')]) . "\n\n";
        flush();

        // Loop and stream physics updates every 1.5 seconds
        while (true) {
            if (connection_aborted()) {
                break;
            }

            // Run simulation cycle
            $payload = $this->simulationService->simulateAllActiveDevices();

            // Push SSE event
            echo "event: telemetry\n";
            echo "data: " . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";
            flush();

            // Wait 1.5 seconds before next cycle
            usleep(1500000);
        }

        exit;
    }

    /**
     * API: Single simulation step (fallback for polling or manual triggers).
     */
    public function apiSimulateStep(): void
    {
        $payload = $this->simulationService->simulateAllActiveDevices();

        $this->json([
            'status' => 'success',
            'data' => $payload,
        ]);
    }

    /**
     * API: Ingest external or manual telemetry point.
     */
    public function apiIngest(): void
    {
        $body = $this->getJsonBody();

        $deviceId = isset($body['device_id']) ? (int)$body['device_id'] : 0;
        $metric = trim($body['metric'] ?? '');
        $value = isset($body['value']) ? (float)$body['value'] : null;
        $unit = trim($body['unit'] ?? '');

        if ($deviceId <= 0 || $metric === '' || $value === null) {
            $this->json([
                'status' => 'error',
                'message' => 'device_id, metric, and numeric value are required.',
            ], 422);
            return;
        }

        $device = $this->deviceModel->find($deviceId);
        if (!$device) {
            $this->json([
                'status' => 'error',
                'message' => "Target device #{$deviceId} does not exist.",
            ], 404);
            return;
        }

        // Fallback units if not supplied
        if ($unit === '') {
            $units = [
                'radiation' => 'µSv/h',
                'temperature' => '°C',
                'pressure' => 'bar',
                'humidity' => '%RH',
                'voltage' => 'V',
                'vibration' => 'mm/s',
            ];
            $unit = $units[strtolower($metric)] ?? 'units';
        }

        $logId = $this->telemetryModel->log($deviceId, $metric, $value, $unit);

        // Evaluate rules
        $incidents = $this->ruleModel->evaluate($deviceId, $metric, $value, $device['name']);

        $this->json([
            'status' => 'success',
            'message' => "Telemetry point logged successfully.",
            'data' => [
                'log_id' => $logId,
                'device_id' => $deviceId,
                'metric' => $metric,
                'value' => $value,
                'unit' => $unit,
                'timestamp' => date('Y-m-d H:i:s'),
                'incidents' => $incidents,
            ],
        ], 201);
    }

    /**
     * API: Query historical telemetry.
     */
    public function apiHistory(): void
    {
        $limit = isset($_GET['limit']) ? min(200, max(1, (int)$_GET['limit'])) : 50;
        $deviceId = isset($_GET['device_id']) && $_GET['device_id'] !== '' ? (int)$_GET['device_id'] : null;
        $metric = isset($_GET['metric']) && $_GET['metric'] !== '' ? trim($_GET['metric']) : null;

        $logs = $this->telemetryModel->getRecent($limit, $deviceId, $metric);

        $this->json([
            'status' => 'success',
            'count' => count($logs),
            'data' => $logs,
        ]);
    }

    /**
     * API: Fleet & Telemetry metrics summary.
     */
    public function apiStats(): void
    {
        $snapshot = $this->telemetryModel->getLatestSnapshot();
        $fleet = $this->deviceModel->getFleetStats();
        $aggregates = $this->telemetryModel->getAggregates();

        $this->json([
            'status' => 'success',
            'snapshot' => $snapshot,
            'fleet' => $fleet,
            'aggregates' => $aggregates,
        ]);
    }

    /**
     * API: Incident feed.
     */
    public function apiIncidents(): void
    {
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 25;
        $incidents = $this->telemetryModel->getRecentIncidents($limit);

        $this->json([
            'status' => 'success',
            'count' => count($incidents),
            'data' => $incidents,
        ]);
    }
}
