<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Device Model for IoT node management and operational status.
 */
class Device
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Retrieve all devices with their latest recorded metric and timestamp.
     *
     * @return array<array<string, mixed>>
     */
    public function all(): array
    {
        $sql = "
            SELECT 
                d.*,
                (
                    SELECT t.value 
                    FROM telemetry_logs t 
                    WHERE t.device_id = d.id 
                    ORDER BY t.timestamp DESC, t.id DESC 
                    LIMIT 1
                ) as latest_value,
                (
                    SELECT t.unit 
                    FROM telemetry_logs t 
                    WHERE t.device_id = d.id 
                    ORDER BY t.timestamp DESC, t.id DESC 
                    LIMIT 1
                ) as latest_unit,
                (
                    SELECT t.timestamp 
                    FROM telemetry_logs t 
                    WHERE t.device_id = d.id 
                    ORDER BY t.timestamp DESC, t.id DESC 
                    LIMIT 1
                ) as latest_timestamp,
                (
                    SELECT COUNT(*) 
                    FROM incidents i 
                    WHERE i.device_id = d.id AND i.triggered_at >= datetime('now', '-24 hours')
                ) as incident_count_24h
            FROM devices d
            ORDER BY d.id ASC
        ";

        return $this->db->select($sql);
    }

    /**
     * Find a device by its ID.
     */
    public function find(int $id): ?array
    {
        return $this->db->selectOne("SELECT * FROM devices WHERE id = :id", ['id' => $id]);
    }

    /**
     * Retrieve all online or active devices for telemetry physics simulation.
     */
    public function getOnlineDevices(): array
    {
        return $this->db->select("SELECT * FROM devices WHERE status != 'offline' ORDER BY id ASC");
    }

    /**
     * Create a new device.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO devices (name, type, status, location, created_at)
            VALUES (:name, :type, :status, :location, CURRENT_TIMESTAMP)
        ";

        return $this->db->insert($sql, [
            'name' => trim($data['name'] ?? 'Unnamed Node'),
            'type' => strtolower(trim($data['type'] ?? 'temperature')),
            'status' => strtolower(trim($data['status'] ?? 'online')),
            'location' => trim($data['location'] ?? 'Grid Sector 1'),
        ]);
    }

    /**
     * Update an existing device.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE devices
            SET name = :name,
                type = :type,
                status = :status,
                location = :location
            WHERE id = :id
        ";

        return $this->db->execute($sql, [
            'id' => $id,
            'name' => trim($data['name']),
            'type' => strtolower(trim($data['type'])),
            'status' => strtolower(trim($data['status'])),
            'location' => trim($data['location']),
        ]) >= 0;
    }

    /**
     * Delete a device and its related telemetry logs and incidents.
     */
    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM devices WHERE id = :id", ['id' => $id]) > 0;
    }

    /**
     * Toggle device status between 'online' and 'offline'.
     */
    public function toggleStatus(int $id): string
    {
        $device = $this->find($id);
        if (!$device) {
            return 'not_found';
        }

        $newStatus = ($device['status'] === 'offline') ? 'online' : 'offline';
        $this->updateStatus($id, $newStatus);

        return $newStatus;
    }

    /**
     * Update specific status ('online', 'warning', 'critical', 'offline').
     */
    public function updateStatus(int $id, string $status): bool
    {
        return $this->db->execute("UPDATE devices SET status = :status WHERE id = :id", [
            'id' => $id,
            'status' => $status,
        ]) > 0;
    }

    /**
     * Get summary metrics for the fleet.
     */
    public function getFleetStats(): array
    {
        $totalDevices = (int)($this->db->selectOne("SELECT COUNT(*) as count FROM devices")['count'] ?? 0);
        $onlineDevices = (int)($this->db->selectOne("SELECT COUNT(*) as count FROM devices WHERE status = 'online'")['count'] ?? 0);
        $warningDevices = (int)($this->db->selectOne("SELECT COUNT(*) as count FROM devices WHERE status = 'warning'")['count'] ?? 0);
        $criticalDevices = (int)($this->db->selectOne("SELECT COUNT(*) as count FROM devices WHERE status = 'critical'")['count'] ?? 0);
        $offlineDevices = (int)($this->db->selectOne("SELECT COUNT(*) as count FROM devices WHERE status = 'offline'")['count'] ?? 0);

        return [
            'total' => $totalDevices,
            'online' => $onlineDevices,
            'warning' => $warningDevices,
            'critical' => $criticalDevices,
            'offline' => $offlineDevices,
            'active_rate' => $totalDevices > 0 ? round((($onlineDevices + $warningDevices) / $totalDevices) * 100, 1) : 0,
        ];
    }
}
