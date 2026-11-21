<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Rule;
use App\Models\TelemetryLog;

/**
 * Controller for Automation Rule Builder and Trigger Audit Logs.
 */
class RuleController extends Controller
{
    private Rule $ruleModel;
    private TelemetryLog $telemetryModel;

    public function __construct()
    {
        $this->ruleModel = new Rule();
        $this->telemetryModel = new TelemetryLog();
    }

    /**
     * Web page: Automation Rules & Incident Policy Center.
     */
    public function index(): void
    {
        $rules = $this->ruleModel->all();
        $incidents = $this->telemetryModel->getRecentIncidents(25);

        $this->view('rules', [
            'title' => 'Automation Rule Builder - OmniMesh Orchestrator',
            'activeTab' => 'rules',
            'rules' => $rules,
            'incidents' => $incidents,
        ]);
    }

    /**
     * API: List all rules.
     */
    public function apiList(): void
    {
        $rules = $this->ruleModel->all();

        $this->json([
            'status' => 'success',
            'data' => $rules,
        ]);
    }

    /**
     * API: Create a new rule.
     */
    public function apiCreate(): void
    {
        $body = $this->getJsonBody();

        $name = trim($body['name'] ?? '');
        $metric = strtolower(trim($body['condition_metric'] ?? ''));
        $operator = trim($body['operator'] ?? '>');
        $threshold = isset($body['threshold']) ? (float)$body['threshold'] : null;
        $action = trim($body['action'] ?? 'ALERT_LOG');
        $isActive = isset($body['is_active']) ? (int)(bool)$body['is_active'] : 1;

        if ($name === '' || $metric === '' || $threshold === null) {
            $this->json([
                'status' => 'error',
                'message' => 'Rule Name, Target Metric, and Threshold value are required.',
            ], 422);
            return;
        }

        $validOperators = ['>', '<', '>=', '<=', '==', '!='];
        if (!in_array($operator, $validOperators, true)) {
            $this->json([
                'status' => 'error',
                'message' => 'Invalid operator. Supported: ' . implode(' ', $validOperators),
            ], 422);
            return;
        }

        $validActions = [
            'TRIGGER_COOLING',
            'VENT_PRESSURE',
            'STATUS_CRITICAL',
            'STATUS_WARNING',
            'ALERT_LOG',
            'SYSTEM_SHUTDOWN',
        ];
        if (!in_array($action, $validActions, true)) {
            $this->json([
                'status' => 'error',
                'message' => 'Invalid action. Allowed: ' . implode(', ', $validActions),
            ], 422);
            return;
        }

        $ruleId = $this->ruleModel->create([
            'name' => $name,
            'condition_metric' => $metric,
            'operator' => $operator,
            'threshold' => $threshold,
            'action' => $action,
            'is_active' => $isActive,
        ]);

        $created = $this->ruleModel->find($ruleId);

        $this->json([
            'status' => 'success',
            'message' => "Automation Rule '{$name}' activated on the grid.",
            'data' => $created,
        ], 201);
    }

    /**
     * API: Toggle rule active/inactive.
     */
    public function apiToggle(mixed $id): void
    {
        $ruleId = (int)$id;
        $rule = $this->ruleModel->find($ruleId);

        if (!$rule) {
            $this->json(['status' => 'error', 'message' => 'Rule not found.'], 404);
            return;
        }

        $newActive = $this->ruleModel->toggleActive($ruleId);

        $this->json([
            'status' => 'success',
            'message' => "Rule #{$ruleId} is now " . ($newActive ? 'ENABLED' : 'DISABLED') . '.',
            'is_active' => $newActive,
            'rule_id' => $ruleId,
        ]);
    }

    /**
     * API: Delete rule.
     */
    public function apiDelete(mixed $id): void
    {
        $ruleId = (int)$id;
        $rule = $this->ruleModel->find($ruleId);

        if (!$rule) {
            $this->json(['status' => 'error', 'message' => 'Rule not found.'], 404);
            return;
        }

        $this->ruleModel->delete($ruleId);

        $this->json([
            'status' => 'success',
            'message' => "Rule #{$ruleId} deleted.",
            'rule_id' => $ruleId,
        ]);
    }

    /**
     * API: List rule trigger incidents.
     */
    public function apiTriggers(): void
    {
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 25;
        $incidents = $this->telemetryModel->getRecentIncidents($limit);

        $this->json([
            'status' => 'success',
            'data' => $incidents,
        ]);
    }
}
