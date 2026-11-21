<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Rule Model & Real-time Evaluation Engine.
 */
class Rule
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all automation rules with their historic trigger counts.
     */
    public function all(): array
    {
        $sql = "
            SELECT 
                r.*,
                (
                    SELECT COUNT(*) 
                    FROM incidents i 
                    WHERE i.rule_id = r.id
                ) as trigger_count,
                (
                    SELECT MAX(i.triggered_at) 
                    FROM incidents i 
                    WHERE i.rule_id = r.id
                ) as last_triggered_at
            FROM rules r
            ORDER BY r.id ASC
        ";

        return $this->db->select($sql);
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne("SELECT * FROM rules WHERE id = :id", ['id' => $id]);
    }

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO rules (name, condition_metric, operator, threshold, action, is_active, created_at)
            VALUES (:name, :condition_metric, :operator, :threshold, :action, :is_active, CURRENT_TIMESTAMP)
        ";

        return $this->db->insert($sql, [
            'name' => trim($data['name'] ?? 'Unnamed Rule'),
            'condition_metric' => strtolower(trim($data['condition_metric'] ?? 'temperature')),
            'operator' => trim($data['operator'] ?? '>'),
            'threshold' => (float)($data['threshold'] ?? 0.0),
            'action' => trim($data['action'] ?? 'ALERT_LOG'),
            'is_active' => isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE rules
            SET name = :name,
                condition_metric = :condition_metric,
                operator = :operator,
                threshold = :threshold,
                action = :action,
                is_active = :is_active
            WHERE id = :id
        ";

        return $this->db->execute($sql, [
            'id' => $id,
            'name' => trim($data['name']),
            'condition_metric' => strtolower(trim($data['condition_metric'])),
            'operator' => trim($data['operator']),
            'threshold' => (float)$data['threshold'],
            'action' => trim($data['action']),
            'is_active' => (int)(bool)$data['is_active'],
        ]) >= 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM rules WHERE id = :id", ['id' => $id]) > 0;
    }

    public function toggleActive(int $id): int
    {
        $rule = $this->find($id);
        if (!$rule) {
            return -1;
        }

        $newStatus = ($rule['is_active'] === 1) ? 0 : 1;
        $this->db->execute("UPDATE rules SET is_active = :is_active WHERE id = :id", [
            'id' => $id,
            'is_active' => $newStatus,
        ]);

        return $newStatus;
    }

    /**
     * Evaluates incoming sensor telemetry against all active rules for that metric.
     * Executes automatic mitigation actions and logs incidents.
     *
     * @return array<array<string, mixed>> Triggered incidents
     */
    public function evaluate(int $deviceId, string $metric, float $value, ?string $deviceName = null): array
    {
        $metric = strtolower(trim($metric));
        $sql = "SELECT * FROM rules WHERE is_active = 1 AND condition_metric = :metric";
        $activeRules = $this->db->select($sql, ['metric' => $metric]);

        $triggered = [];
        $deviceModel = new Device();

        foreach ($activeRules as $rule) {
            $threshold = (float)$rule['threshold'];
            $operator = $rule['operator'];
            $isBreached = false;

            switch ($operator) {
                case '>':
                    $isBreached = ($value > $threshold);
                    break;
                case '>=':
                    $isBreached = ($value >= $threshold);
                    break;
                case '<':
                    $isBreached = ($value < $threshold);
                    break;
                case '<=':
                    $isBreached = ($value <= $threshold);
                    break;
                case '==':
                case '=':
                    $isBreached = (abs($value - $threshold) < 0.0001);
                    break;
                case '!=':
                    $isBreached = (abs($value - $threshold) >= 0.0001);
                    break;
            }

            if ($isBreached) {
                $action = $rule['action'];
                $severity = 'warning';

                if (in_array($action, ['STATUS_CRITICAL', 'SYSTEM_SHUTDOWN'], true)) {
                    $severity = 'critical';
                } elseif ($action === 'ALERT_LOG') {
                    $severity = 'info';
                }

                // Execute Automated Action
                $actionNote = '';
                switch ($action) {
                    case 'STATUS_CRITICAL':
                        $deviceModel->updateStatus($deviceId, 'critical');
                        $actionNote = "Node status shifted to CRITICAL.";
                        break;
                    case 'STATUS_WARNING':
                        $device = $deviceModel->find($deviceId);
                        if ($device && $device['status'] !== 'critical') {
                            $deviceModel->updateStatus($deviceId, 'warning');
                        }
                        $actionNote = "Node status shifted to WARNING.";
                        break;
                    case 'TRIGGER_COOLING':
                        $actionNote = "Auxiliary cryo-coolant loop auto-engaged.";
                        $deviceModel->updateStatus($deviceId, 'warning');
                        break;
                    case 'VENT_PRESSURE':
                        $actionNote = "Pneumatic relief valve pulsed open.";
                        $deviceModel->updateStatus($deviceId, 'warning');
                        break;
                    case 'SYSTEM_SHUTDOWN':
                        $deviceModel->updateStatus($deviceId, 'offline');
                        $actionNote = "Emergency interlock triggered node isolate.";
                        break;
                    case 'ALERT_LOG':
                    default:
                        $actionNote = "Telemetry anomaly logged to audit trail.";
                        break;
                }

                $message = sprintf(
                    "Rule '%s' breached: %s is %.2f (%s %.2f). %s",
                    $rule['name'],
                    strtoupper($metric),
                    $value,
                    $operator,
                    $threshold,
                    $actionNote
                );

                // Insert incident into DB
                $incidentId = $this->db->insert(
                    "INSERT INTO incidents (rule_id, device_id, metric, value, message, severity, triggered_at)
                     VALUES (:rule_id, :device_id, :metric, :value, :message, :severity, CURRENT_TIMESTAMP)",
                    [
                        'rule_id' => $rule['id'],
                        'device_id' => $deviceId,
                        'metric' => $metric,
                        'value' => $value,
                        'message' => $message,
                        'severity' => $severity,
                    ]
                );

                $triggered[] = [
                    'id' => $incidentId,
                    'rule_id' => $rule['id'],
                    'rule_name' => $rule['name'],
                    'device_id' => $deviceId,
                    'device_name' => $deviceName ?? "Device #{$deviceId}",
                    'metric' => $metric,
                    'value' => $value,
                    'action' => $action,
                    'message' => $message,
                    'severity' => $severity,
                    'time_label' => date('H:i:s'),
                ];
            }
        }

        return $triggered;
    }
}
