<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * TelemetryLog Model: Time-series query engine and incident recorder.
 */
class TelemetryLog
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Store a new telemetry reading.
     */
    public function log(int $deviceId, string $metric, float $value, string $unit, ?string $timestamp = null): int
    {
        $sql = "
            INSERT INTO telemetry_logs (device_id, metric, value, unit, timestamp)
            VALUES (:device_id, :metric, :value, :unit, COALESCE(:timestamp, CURRENT_TIMESTAMP))
        ";

        return $this->db->insert($sql, [
            'device_id' => $deviceId,
            'metric' => strtolower(trim($metric)),
            'value' => round($value, 3),
            'unit' => trim($unit),
            'timestamp' => $timestamp,
        ]);
    }

    /**
     * Retrieve recent telemetry records with optional device or metric filter.
     */
    public function getRecent(int $limit = 50, ?int $deviceId = null, ?string $metric = null): array
    {
        $conditions = [];
        $params = [];

        if ($deviceId !== null) {
            $conditions[] = 't.device_id = :device_id';
            $params['device_id'] = $deviceId;
        }

        if ($metric !== null && $metric !== '') {
            $conditions[] = 't.metric = :metric';
            $params['metric'] = strtolower($metric);
        }

        $whereClause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "
            SELECT 
                t.id,
                t.device_id,
                t.metric,
                t.value,
                t.unit,
                t.timestamp,
                d.name as device_name,
                d.type as device_type,
                d.location as device_location
            FROM telemetry_logs t
            JOIN devices d ON d.id = t.device_id
            {$whereClause}
            ORDER BY t.timestamp DESC, t.id DESC
            LIMIT {$limit}
        ";

        return $this->db->select($sql, $params);
    }

    /**
     * Retrieve chronological time-series points for charting (ascending order).
     */
    public function getTimeSeries(string $metric, int $limit = 30): array
    {
        $sql = "
            SELECT 
                t.id,
                t.device_id,
                t.metric,
                t.value,
                t.unit,
                strftime('%H:%M:%S', t.timestamp) as time_label,
                t.timestamp
            FROM telemetry_logs t
            WHERE t.metric = :metric
            ORDER BY t.timestamp DESC, t.id DESC
            LIMIT {$limit}
        ";

        $records = $this->db->select($sql, ['metric' => strtolower($metric)]);
        // Return chronologically for chart rendering
        return array_reverse($records);
    }

    /**
     * Get the latest values for key dashboard metrics (temperature, pressure, radiation, humidity).
     */
    public function getLatestSnapshot(): array
    {
        $metrics = ['radiation', 'temperature', 'pressure', 'humidity', 'voltage', 'vibration'];
        $snapshot = [];

        foreach ($metrics as $metric) {
            $sql = "
                SELECT 
                    t.value, 
                    t.unit, 
                    t.timestamp, 
                    d.id as device_id, 
                    d.name as device_name 
                FROM telemetry_logs t
                JOIN devices d ON d.id = t.device_id
                WHERE t.metric = :metric
                ORDER BY t.timestamp DESC, t.id DESC
                LIMIT 1
            ";
            $row = $this->db->selectOne($sql, ['metric' => $metric]);
            if ($row) {
                $snapshot[$metric] = [
                    'value' => (float)$row['value'],
                    'unit' => $row['unit'],
                    'timestamp' => $row['timestamp'],
                    'device_name' => $row['device_name'],
                    'device_id' => (int)$row['device_id'],
                ];
            } else {
                // Defaults if no log yet
                $defaults = [
                    'radiation' => ['value' => 0.15, 'unit' => 'µSv/h'],
                    'temperature' => ['value' => 24.5, 'unit' => '°C'],
                    'pressure' => ['value' => 1.013, 'unit' => 'bar'],
                    'humidity' => ['value' => 45.0, 'unit' => '%RH'],
                    'voltage' => ['value' => 230.0, 'unit' => 'V'],
                    'vibration' => ['value' => 1.2, 'unit' => 'mm/s'],
                ];
                $snapshot[$metric] = [
                    'value' => $defaults[$metric]['value'],
                    'unit' => $defaults[$metric]['unit'],
                    'timestamp' => date('Y-m-d H:i:s'),
                    'device_name' => 'Default Sensor',
                    'device_id' => 0,
                ];
            }
        }

        return $snapshot;
    }

    /**
     * Log an incident caused by an automated rule breach or anomaly.
     */
    public function logIncident(
        ?int $ruleId,
        ?int $deviceId,
        string $metric,
        float $value,
        string $message,
        string $severity = 'warning'
    ): int {
        $sql = "
            INSERT INTO incidents (rule_id, device_id, metric, value, message, severity, triggered_at)
            VALUES (:rule_id, :device_id, :metric, :value, :message, :severity, CURRENT_TIMESTAMP)
        ";

        return $this->db->insert($sql, [
            'rule_id' => $ruleId,
            'device_id' => $deviceId,
            'metric' => $metric,
            'value' => $value,
            'message' => $message,
            'severity' => in_array($severity, ['info', 'warning', 'critical'], true) ? $severity : 'warning',
        ]);
    }

    /**
     * Retrieve recent incidents for the live ticker.
     */
    public function getRecentIncidents(int $limit = 20): array
    {
        $sql = "
            SELECT 
                i.id,
                i.rule_id,
                i.device_id,
                i.metric,
                i.value,
                i.message,
                i.severity,
                i.triggered_at,
                strftime('%H:%M:%S', i.triggered_at) as time_label,
                COALESCE(r.name, 'System Anomaly') as rule_name,
                COALESCE(r.action, 'ALERT_LOG') as rule_action,
                COALESCE(d.name, 'Mesh Grid') as device_name,
                d.location as device_location
            FROM incidents i
            LEFT JOIN rules r ON r.id = i.rule_id
            LEFT JOIN devices d ON d.id = i.device_id
            ORDER BY i.triggered_at DESC, i.id DESC
            LIMIT {$limit}
        ";

        return $this->db->select($sql);
    }

    /**
     * Aggregation statistics for telemetry points.
     */
    public function getAggregates(): array
    {
        $totalPoints = (int)($this->db->selectOne("SELECT COUNT(*) as count FROM telemetry_logs")['count'] ?? 0);
        $totalIncidents = (int)($this->db->selectOne("SELECT COUNT(*) as count FROM incidents")['count'] ?? 0);
        $criticalIncidents = (int)($this->db->selectOne("SELECT COUNT(*) as count FROM incidents WHERE severity = 'critical'")['count'] ?? 0);

        return [
            'total_points' => $totalPoints,
            'total_incidents' => $totalIncidents,
            'critical_incidents' => $criticalIncidents,
        ];
    }
}
