<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Device;
use App\Models\TelemetryLog;

/**
 * Controller for Device Fleet operations and Web UI views.
 */
class DeviceController extends Controller
{
    private Device $deviceModel;
    private TelemetryLog $telemetryModel;

    public function __construct()
    {
        $this->deviceModel = new Device();
        $this->telemetryModel = new TelemetryLog();
    }

    /**
     * Web page: Device Fleet Management Interface.
     */
    public function index(): void
    {
        $devices = $this->deviceModel->all();
        $fleetStats = $this->deviceModel->getFleetStats();

        $this->view('devices', [
            'title' => 'Device Fleet Inventory - OmniMesh Orchestrator',
            'activeTab' => 'devices',
            'devices' => $devices,
            'stats' => $fleetStats,
        ]);
    }

    /**
     * API: List all devices with latest metrics.
     */
    public function apiList(): void
    {
        $devices = $this->deviceModel->all();
        $stats = $this->deviceModel->getFleetStats();

        $this->json([
            'status' => 'success',
            'data' => $devices,
            'stats' => $stats,
        ]);
    }

    /**
     * API: Register a new IoT device.
     */
    public function apiCreate(): void
    {
        $body = $this->getJsonBody();

        $name = trim($body['name'] ?? '');
        $type = trim($body['type'] ?? '');
        $location = trim($body['location'] ?? '');
        $status = trim($body['status'] ?? 'online');

        if ($name === '' || $type === '' || $location === '') {
            $this->json([
                'status' => 'error',
                'message' => 'Node Name, Type, and Installation Location are required fields.',
            ], 422);
            return;
        }

        $validTypes = ['radiation', 'temperature', 'pressure', 'humidity', 'vibration', 'voltage'];
        if (!in_array(strtolower($type), $validTypes, true)) {
            $this->json([
                'status' => 'error',
                'message' => 'Invalid sensor type. Allowed: ' . implode(', ', $validTypes),
            ], 422);
            return;
        }

        $deviceId = $this->deviceModel->create([
            'name' => $name,
            'type' => $type,
            'status' => in_array($status, ['online', 'offline', 'warning', 'critical'], true) ? $status : 'online',
            'location' => $location,
        ]);

        $newDevice = $this->deviceModel->find($deviceId);

        $this->json([
            'status' => 'success',
            'message' => "Telemetry Node #{$deviceId} '{$name}' deployed successfully.",
            'data' => $newDevice,
        ], 201);
    }

    /**
     * API: Toggle device online / offline.
     */
    public function apiToggle(mixed $id): void
    {
        $deviceId = (int)$id;
        $device = $this->deviceModel->find($deviceId);

        if (!$device) {
            $this->json(['status' => 'error', 'message' => 'Device not found.'], 404);
            return;
        }

        $newStatus = $this->deviceModel->toggleStatus($deviceId);

        $this->json([
            'status' => 'success',
            'message' => "Device #{$deviceId} status switched to {$newStatus}.",
            'new_status' => $newStatus,
            'device_id' => $deviceId,
        ]);
    }

    /**
     * API: Update device details.
     */
    public function apiUpdate(mixed $id): void
    {
        $deviceId = (int)$id;
        $device = $this->deviceModel->find($deviceId);

        if (!$device) {
            $this->json(['status' => 'error', 'message' => 'Device not found.'], 404);
            return;
        }

        $body = $this->getJsonBody();
        $name = trim($body['name'] ?? $device['name']);
        $type = trim($body['type'] ?? $device['type']);
        $location = trim($body['location'] ?? $device['location']);
        $status = trim($body['status'] ?? $device['status']);

        $this->deviceModel->update($deviceId, [
            'name' => $name,
            'type' => $type,
            'location' => $location,
            'status' => $status,
        ]);

        $updated = $this->deviceModel->find($deviceId);

        $this->json([
            'status' => 'success',
            'message' => "Device #{$deviceId} updated successfully.",
            'data' => $updated,
        ]);
    }

    /**
     * API: Delete device node.
     */
    public function apiDelete(mixed $id): void
    {
        $deviceId = (int)$id;
        $device = $this->deviceModel->find($deviceId);

        if (!$device) {
            $this->json(['status' => 'error', 'message' => 'Device not found.'], 404);
            return;
        }

        $this->deviceModel->delete($deviceId);

        $this->json([
            'status' => 'success',
            'message' => "Device #{$deviceId} and associated logs purged.",
            'device_id' => $deviceId,
        ]);
    }
}
