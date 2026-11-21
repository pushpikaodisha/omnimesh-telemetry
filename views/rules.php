<?php
/**
 * Automation Rule Builder & Trigger Logs View
 *
 * @var array $rules
 * @var array $incidents
 */
?>

<div class="rules-deck">
    <!-- Top Policy Builder Section -->
    <div class="rules-top-grid">
        <!-- New Rule Form Panel -->
        <div class="glass-card form-panel">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">CREATE AUTOMATION RULE</h3>
                    <p class="panel-desc">Define real-time condition triggers and automatic hardware mitigations</p>
                </div>
            </div>

            <form id="formCreateRule" class="rule-builder-form">
                <div class="form-group">
                    <label for="ruleName" class="form-label">Rule Policy Name</label>
                    <input type="text" id="ruleName" class="form-control" placeholder="e.g. Auxiliary Core Cryo Trigger" required>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label for="ruleMetric" class="form-label">Sensor Metric</label>
                        <select id="ruleMetric" class="form-control" required>
                            <option value="temperature">Temperature (°C)</option>
                            <option value="pressure">Pressure (bar)</option>
                            <option value="radiation">Radiation (µSv/h)</option>
                            <option value="humidity">Humidity (%RH)</option>
                            <option value="vibration">Vibration (mm/s)</option>
                            <option value="voltage">Voltage (V)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="ruleOperator" class="form-label">Condition</label>
                        <select id="ruleOperator" class="form-control" required>
                            <option value=">">Greater than (&gt;)</option>
                            <option value=">=">Greater than or equal (&gt;=)</option>
                            <option value="<">Less than (&lt;)</option>
                            <option value="<=">Less than or equal (&lt;=)</option>
                            <option value="==">Exact match (==)</option>
                            <option value="!=">Not equal (!=)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="ruleThreshold" class="form-label">Threshold Value</label>
                        <input type="number" step="0.01" id="ruleThreshold" class="form-control" placeholder="e.g. 85.0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ruleAction" class="form-label">Autonomous Mitigation Action</label>
                    <select id="ruleAction" class="form-control" required>
                        <option value="TRIGGER_COOLING">TRIGGER_COOLING (Engage Auxiliary Cryo Coolant Loop)</option>
                        <option value="VENT_PRESSURE">VENT_PRESSURE (Pulse Open Pneumatic Relief Valve)</option>
                        <option value="STATUS_CRITICAL">STATUS_CRITICAL (Lockout Device into Critical Alarm)</option>
                        <option value="STATUS_WARNING">STATUS_WARNING (Escalate Device to Warning State)</option>
                        <option value="ALERT_LOG">ALERT_LOG (Record Incident in Event Log)</option>
                        <option value="SYSTEM_SHUTDOWN">SYSTEM_SHUTDOWN (Emergency Isolate Sensor Node)</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="btnSubmitRule">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        Arm Automation Rule
                    </button>
                </div>
            </form>
        </div>

        <!-- Automation Engine Information Box -->
        <div class="glass-card info-panel">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">RULE ENGINE ARCHITECTURE</h3>
                    <p class="panel-desc">Real-time evaluation semantics</p>
                </div>
            </div>
            <div class="info-content">
                <div class="info-step">
                    <div class="step-num">01</div>
                    <div class="step-desc">
                        <strong>Deterministic Evaluation</strong>
                        <p>Every incoming telemetry point (live SSE tick or REST POST) is checked against active operator conditions.</p>
                    </div>
                </div>
                <div class="info-step">
                    <div class="step-num">02</div>
                    <div class="step-desc">
                        <strong>Instant Hardware Mitigation</strong>
                        <p>Automated actions trigger immediate node status mutations (e.g. safety lockout, cryo cooling engagement).</p>
                    </div>
                </div>
                <div class="info-step">
                    <div class="step-num">03</div>
                    <div class="step-desc">
                        <strong>Immutable Incident Log</strong>
                        <p>Breaches are timestamped, logged with exact telemetry values, and streamed to the dashboard ticker.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Rules Table Panel -->
    <div class="glass-card table-panel">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">ACTIVE AUTOMATION POLICIES</h3>
                <p class="panel-desc">Rules armed and evaluating incoming sensor streams</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="mesh-table" id="rulesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>POLICY NAME</th>
                        <th>TARGET METRIC</th>
                        <th>TRIGGER CONDITION</th>
                        <th>MITIGATION ACTION</th>
                        <th>HISTORIC TRIGGERS</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="rulesTableBody">
                    <?php if (empty($rules)): ?>
                        <tr><td colspan="8" class="text-center text-dim" style="padding: 2rem;">No automation rules armed. Create one above.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rules as $r): ?>
                            <tr id="rule-row-<?= $r['id'] ?>">
                                <td class="col-mono text-cyan">#<?= str_pad((string)$r['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td><strong><?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td>
                                    <span class="badge badge-type badge-type-<?= htmlspecialchars($r['condition_metric'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= strtoupper($r['condition_metric']) ?>
                                    </span>
                                </td>
                                <td class="col-mono text-amber">
                                    <?= htmlspecialchars($r['operator'], ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$r['threshold'], 2) ?>
                                </td>
                                <td>
                                    <span class="badge badge-action">
                                        <?= htmlspecialchars($r['action'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="col-mono text-cyan">
                                    <span id="ruleTrigCount-<?= $r['id'] ?>"><?= $r['trigger_count'] ?? 0 ?></span> events
                                </td>
                                <td>
                                    <button class="rule-toggle-btn <?= $r['is_active'] ? 'active' : '' ?>" data-id="<?= $r['id'] ?>" id="btnRuleToggle-<?= $r['id'] ?>">
                                        <?= $r['is_active'] ? 'ACTIVE' : 'DISABLED' ?>
                                    </button>
                                </td>
                                <td>
                                    <button class="btn btn-danger btn-xs btn-delete-rule" data-id="<?= $r['id'] ?>" data-name="<?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?>">
                                        Purge
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Incident History Audit Table -->
    <div class="glass-card table-panel">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">INCIDENT AUDIT LOG</h3>
                <p class="panel-desc">Chronological register of automated safety actions and threshold triggers</p>
            </div>
            <button class="btn btn-secondary btn-sm" id="btnRefreshAudit">Refresh Log</button>
        </div>

        <div class="table-responsive">
            <table class="mesh-table" id="incidentsTable">
                <thead>
                    <tr>
                        <th>TIMESTAMP</th>
                        <th>SEVERITY</th>
                        <th>TARGET NODE</th>
                        <th>TRIGGERED VALUE</th>
                        <th>POLICY BREACHED</th>
                        <th>INCIDENT SUMMARY &amp; MITIGATION</th>
                    </tr>
                </thead>
                <tbody id="incidentsTableBody">
                    <?php if (empty($incidents)): ?>
                        <tr><td colspan="6" class="text-center text-dim" style="padding: 2rem;">No incident records found. System operating nominally.</td></tr>
                    <?php else: ?>
                        <?php foreach ($incidents as $inc): ?>
                            <tr>
                                <td class="col-mono text-dim"><?= htmlspecialchars($inc['triggered_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-<?= htmlspecialchars($inc['severity'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= strtoupper($inc['severity']) ?>
                                    </span>
                                </td>
                                <td><strong><?= htmlspecialchars($inc['device_name'] ?? 'Grid Node', ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td class="col-mono text-amber"><?= number_format((float)$inc['value'], 2) ?> (<?= htmlspecialchars($inc['metric'], ENT_QUOTES, 'UTF-8') ?>)</td>
                                <td><?= htmlspecialchars($inc['rule_name'] ?? 'Custom Anomaly', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($inc['message'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
