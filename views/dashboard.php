<?php
/**
 * Dashboard View - Mission Control
 *
 * @var array $snapshot
 * @var array $devices
 * @var array $incidents
 * @var array $stats
 * @var array $aggregates
 * @var array $chartSeries
 */
?>

<!-- Pass initial backend data to JavaScript via JSON scripts -->
<script id="initSnapshotData" type="application/json"><?= json_encode($snapshot) ?></script>
<script id="initChartSeries" type="application/json"><?= json_encode($chartSeries) ?></script>

<div class="dashboard-deck">
    <!-- Top KPI Stat Banners -->
    <section class="stat-grid">
        <div class="stat-card glass-card">
            <div class="stat-icon-wrap icon-cyan">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">ONLINE NODES</div>
                <div class="stat-value" id="kpiOnlineNodes"><?= $stats['online'] ?? 0 ?> / <?= $stats['total'] ?? 0 ?></div>
                <div class="stat-delta text-emerald">Active Mesh Coverage: <span id="kpiActiveRate"><?= $stats['active_rate'] ?? 0 ?>%</span></div>
            </div>
        </div>

        <div class="stat-card glass-card">
            <div class="stat-icon-wrap icon-emerald">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">TELEMETRY INGESTED</div>
                <div class="stat-value" id="kpiTotalPoints"><?= number_format($aggregates['total_points'] ?? 0) ?></div>
                <div class="stat-delta text-cyan">Real-time Stream: ~40 pts/min</div>
            </div>
        </div>

        <div class="stat-card glass-card">
            <div class="stat-icon-wrap icon-amber">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">RULE INTERVENTIONS</div>
                <div class="stat-value" id="kpiTotalIncidents"><?= $aggregates['total_incidents'] ?? 0 ?></div>
                <div class="stat-delta text-amber">Active Automated Mitigations</div>
            </div>
        </div>

        <div class="stat-card glass-card">
            <div class="stat-icon-wrap icon-crimson">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">CRITICAL BREACHES</div>
                <div class="stat-value" id="kpiCriticalIncidents"><?= $aggregates['critical_incidents'] ?? 0 ?></div>
                <div class="stat-delta <?= ($aggregates['critical_incidents'] ?? 0) > 0 ? 'text-crimson' : 'text-emerald' ?>">
                    <?= ($aggregates['critical_incidents'] ?? 0) > 0 ? 'Safety Interlocks Engaged' : 'Containment Nominal' ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Real-time Physical Dial Gauges Section -->
    <section class="section-block">
        <div class="section-header">
            <div class="section-title-wrap">
                <h2 class="section-title">PRIMARY TELEMETRY GAUGES</h2>
                <span class="section-subtitle">PHYSICS DYNAMICS &amp; ANOMALY SENSORS</span>
            </div>
            <div class="section-actions">
                <span class="badge badge-glow-cyan"><span class="pulse-dot"></span> LIVE 1.5S TICK</span>
            </div>
        </div>

        <div class="gauge-grid">
            <!-- Gauge 1: Radiation -->
            <div class="glass-card gauge-card">
                <div class="gauge-card-header">
                    <span class="gauge-title">GAMMA / BETA FLUX</span>
                    <span class="gauge-id" id="radDeviceId">NODE #1</span>
                </div>
                <div class="gauge-canvas-container">
                    <canvas id="gaugeRadiation" width="220" height="180"></canvas>
                </div>
                <div class="gauge-footer">
                    <div class="gauge-digital-readout">
                        <span class="readout-val text-cyan" id="radReadout"><?= number_format($snapshot['radiation']['value'] ?? 0.16, 3) ?></span>
                        <span class="readout-unit">µSv/h</span>
                    </div>
                    <div class="gauge-threshold-guide">Threshold: &gt; 0.65 µSv/h</div>
                </div>
            </div>

            <!-- Gauge 2: Temperature -->
            <div class="glass-card gauge-card">
                <div class="gauge-card-header">
                    <span class="gauge-title">CORE THERMAL</span>
                    <span class="gauge-id" id="tempDeviceId">NODE #2</span>
                </div>
                <div class="gauge-canvas-container">
                    <canvas id="gaugeTemperature" width="220" height="180"></canvas>
                </div>
                <div class="gauge-footer">
                    <div class="gauge-digital-readout">
                        <span class="readout-val text-emerald" id="tempReadout"><?= number_format($snapshot['temperature']['value'] ?? 52.0, 1) ?></span>
                        <span class="readout-unit">°C</span>
                    </div>
                    <div class="gauge-threshold-guide">Cooling Trigger: &gt; 82.0 °C</div>
                </div>
            </div>

            <!-- Gauge 3: Pressure -->
            <div class="glass-card gauge-card">
                <div class="gauge-card-header">
                    <span class="gauge-title">CHAMBER PRESSURE</span>
                    <span class="gauge-id" id="pressDeviceId">NODE #3</span>
                </div>
                <div class="gauge-canvas-container">
                    <canvas id="gaugePressure" width="220" height="180"></canvas>
                </div>
                <div class="gauge-footer">
                    <div class="gauge-digital-readout">
                        <span class="readout-val text-amber" id="pressReadout"><?= number_format($snapshot['pressure']['value'] ?? 8.5, 2) ?></span>
                        <span class="readout-unit">bar</span>
                    </div>
                    <div class="gauge-threshold-guide">Vent Limit: &gt; 12.0 bar</div>
                </div>
            </div>

            <!-- Gauge 4: Humidity -->
            <div class="glass-card gauge-card">
                <div class="gauge-card-header">
                    <span class="gauge-title">ATMOSPHERIC MOISTURE</span>
                    <span class="gauge-id" id="humidDeviceId">NODE #4</span>
                </div>
                <div class="gauge-canvas-container">
                    <canvas id="gaugeHumidity" width="220" height="180"></canvas>
                </div>
                <div class="gauge-footer">
                    <div class="gauge-digital-readout">
                        <span class="readout-val text-violet" id="humidReadout"><?= number_format($snapshot['humidity']['value'] ?? 48.0, 1) ?></span>
                        <span class="readout-unit">%RH</span>
                    </div>
                    <div class="gauge-threshold-guide">Excursion Warning: &gt; 70 %RH</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Multi-Series Waveform Rolling Chart & Incident Ticker Split -->
    <div class="dashboard-split-grid">
        <!-- Live Waveform Chart -->
        <div class="glass-card chart-panel">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">REAL-TIME MULTI-WAVE OSCILLOSCOPE</h3>
                    <p class="panel-desc">Time-series sine harmonics &amp; physics jitter telemetry</p>
                </div>
                <div class="chart-legend-controls">
                    <button class="legend-chip active" data-metric="temperature" id="toggleTemp">
                        <span class="legend-color-dot" style="background: #10b981;"></span> Temp (°C)
                    </button>
                    <button class="legend-chip active" data-metric="pressure" id="togglePress">
                        <span class="legend-color-dot" style="background: #f59e0b;"></span> Press (bar)
                    </button>
                    <button class="legend-chip active" data-metric="humidity" id="toggleHumid">
                        <span class="legend-color-dot" style="background: #8b5cf6;"></span> Humidity (%)
                    </button>
                    <button class="legend-chip active" data-metric="radiation" id="toggleRad">
                        <span class="legend-color-dot" style="background: #00f0ff;"></span> Rad (µSv/h)
                    </button>
                </div>
            </div>
            <div class="chart-canvas-wrapper">
                <canvas id="oscilloscopeChart" width="800" height="280"></canvas>
            </div>
        </div>

        <!-- Incident & Rule Action Ticker -->
        <div class="glass-card ticker-panel">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">AUTOMATION &amp; INCIDENT TICKER</h3>
                    <p class="panel-desc">Rule breaches and automatic mitigation events</p>
                </div>
                <button class="btn btn-secondary btn-sm" id="btnClearTicker">Clear View</button>
            </div>
            <div class="ticker-stream" id="incidentTickerStream">
                <?php if (empty($incidents)): ?>
                    <div class="ticker-empty" id="tickerEmpty">No anomaly incidents recorded. All sensor streams operating in optimal thresholds.</div>
                <?php else: ?>
                    <?php foreach ($incidents as $inc): ?>
                        <div class="ticker-item severity-<?= htmlspecialchars($inc['severity'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="ticker-item-meta">
                                <span class="badge badge-<?= htmlspecialchars($inc['severity'], ENT_QUOTES, 'UTF-8') ?>"><?= strtoupper($inc['severity']) ?></span>
                                <span class="ticker-time"><?= htmlspecialchars($inc['time_label'] ?? substr($inc['triggered_at'], 11, 8), ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ticker-device"><?= htmlspecialchars($inc['device_name'] ?? 'Mesh Sensor', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="ticker-message"><?= htmlspecialchars($inc['message'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="ticker-action">Action: <code><?= htmlspecialchars($inc['rule_action'] ?? 'ALERT_LOG', ENT_QUOTES, 'UTF-8') ?></code></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Active Device Fleet Overview Cards -->
    <section class="section-block">
        <div class="section-header">
            <div class="section-title-wrap">
                <h2 class="section-title">ACTIVE MESH FLEET</h2>
                <span class="section-subtitle">CONNECTED SENSOR NODES &amp; HARDWARE INTERFACES</span>
            </div>
            <div class="section-actions">
                <a href="/devices" class="btn btn-primary btn-sm">Provision New Node &rarr;</a>
            </div>
        </div>

        <div class="device-card-grid" id="dashboardDeviceGrid">
            <?php foreach ($devices as $dev): ?>
                <div class="glass-card device-item-card status-<?= htmlspecialchars($dev['status'], ENT_QUOTES, 'UTF-8') ?>" id="devCard-<?= $dev['id'] ?>">
                    <div class="device-item-header">
                        <div class="device-name-type">
                            <span class="device-type-badge"><?= strtoupper($dev['type']) ?></span>
                            <h4 class="device-name"><?= htmlspecialchars($dev['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                        </div>
                        <span class="status-indicator status-<?= htmlspecialchars($dev['status'], ENT_QUOTES, 'UTF-8') ?>" id="devStatusBadge-<?= $dev['id'] ?>">
                            <?= strtoupper($dev['status']) ?>
                        </span>
                    </div>
                    <div class="device-location">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <span><?= htmlspecialchars($dev['location'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="device-metrics-preview">
                        <div class="metric-preview-item">
                            <span class="preview-label">Current Reading</span>
                            <span class="preview-val" id="devVal-<?= $dev['id'] ?>">
                                <?= isset($dev['latest_value']) ? number_format((float)$dev['latest_value'], 2) . ' ' . $dev['latest_unit'] : 'Pending stream...' ?>
                            </span>
                        </div>
                        <div class="metric-preview-item">
                            <span class="preview-label">24h Incidents</span>
                            <span class="preview-val text-amber"><?= $dev['incident_count_24h'] ?? 0 ?></span>
                        </div>
                    </div>
                    <div class="device-card-actions">
                        <button class="btn btn-secondary btn-sm btn-block toggle-device-btn" data-id="<?= $dev['id'] ?>" id="btnToggle-<?= $dev['id'] ?>">
                            <?= $dev['status'] === 'offline' ? 'Power ON' : 'Deactivate' ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
