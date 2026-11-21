<?php
/**
 * Device Fleet Management View
 *
 * @var array $devices
 * @var array $stats
 */
?>

<div class="devices-deck">
    <!-- Fleet Statistics Bar -->
    <section class="stat-grid">
        <div class="stat-card glass-card">
            <div class="stat-icon-wrap icon-cyan">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect></svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">TOTAL SENSOR FLEET</div>
                <div class="stat-value" id="fleetTotal"><?= $stats['total'] ?? 0 ?></div>
                <div class="stat-delta text-cyan">Provisioned Hardware</div>
            </div>
        </div>

        <div class="stat-card glass-card">
            <div class="stat-icon-wrap icon-emerald">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">ONLINE &amp; TRANSMITTING</div>
                <div class="stat-value" id="fleetOnline"><?= $stats['online'] ?? 0 ?></div>
                <div class="stat-delta text-emerald"><?= $stats['active_rate'] ?? 0 ?>% Active Transmit</div>
            </div>
        </div>

        <div class="stat-card glass-card">
            <div class="stat-icon-wrap icon-amber">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">WARNING THRESHOLDS</div>
                <div class="stat-value" id="fleetWarning"><?= $stats['warning'] ?? 0 ?></div>
                <div class="stat-delta text-amber">Active Throttling/Cooling</div>
            </div>
        </div>

        <div class="stat-card glass-card">
            <div class="stat-icon-wrap icon-crimson">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">OFFLINE / DORMANT</div>
                <div class="stat-value" id="fleetOffline"><?= $stats['offline'] ?? 0 ?></div>
                <div class="stat-delta text-dim">Decommissioned or Power-Gated</div>
            </div>
        </div>
    </section>

    <!-- Toolbar: Search, Filter & Provision Modal Trigger -->
    <div class="glass-card fleet-toolbar">
        <div class="toolbar-search">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="deviceSearchInput" placeholder="Filter by node name or location..." class="form-control">
        </div>
        <div class="toolbar-filters">
            <select id="deviceTypeFilter" class="form-control">
                <option value="all">All Sensor Types</option>
                <option value="radiation">Radiation (Gamma/Beta)</option>
                <option value="temperature">Temperature (Core/Coolant)</option>
                <option value="pressure">Pressure (Hydraulic/Pneumatic)</option>
                <option value="humidity">Humidity (Atmospheric)</option>
                <option value="voltage">Voltage (Substation Phase)</option>
                <option value="vibration">Vibration (Harmonic Resonance)</option>
            </select>
            <button class="btn btn-primary" id="btnOpenAddDeviceModal">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Provision Sensor Node
            </button>
        </div>
    </div>

    <!-- Device Fleet Table View -->
    <div class="glass-card table-panel">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">TELEMETRY NODE INVENTORY</h3>
                <p class="panel-desc">Physical and virtual sensor points connected across the OmniMesh backbone</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="mesh-table" id="devicesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NODE SPECIFICATION</th>
                        <th>PHYSICAL TYPE</th>
                        <th>LOCATION / ZONE</th>
                        <th>STATUS</th>
                        <th>LATEST TELEMETRY</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="devicesTableBody">
                    <?php if (empty($devices)): ?>
                        <tr><td colspan="7" class="text-center text-dim" style="padding: 2rem;">No sensors configured. Click "Provision Sensor Node" to deploy.</td></tr>
                    <?php else: ?>
                        <?php foreach ($devices as $dev): ?>
                            <tr id="row-dev-<?= $dev['id'] ?>" data-type="<?= htmlspecialchars($dev['type'], ENT_QUOTES, 'UTF-8') ?>" data-status="<?= htmlspecialchars($dev['status'], ENT_QUOTES, 'UTF-8') ?>">
                                <td class="col-mono text-cyan">#<?= str_pad((string)$dev['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div class="dev-name-col">
                                        <strong><?= htmlspecialchars($dev['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span class="sub-created">Commissioned: <?= substr($dev['created_at'], 0, 10) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-type badge-type-<?= htmlspecialchars($dev['type'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= strtoupper($dev['type']) ?>
                                    </span>
                                </td>
                                <td class="text-dim"><?= htmlspecialchars($dev['location'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="status-indicator status-<?= htmlspecialchars($dev['status'], ENT_QUOTES, 'UTF-8') ?>" id="tableStatus-<?= $dev['id'] ?>">
                                        <?= strtoupper($dev['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="telemetry-cell">
                                        <span class="val" id="tableVal-<?= $dev['id'] ?>">
                                            <?= isset($dev['latest_value']) ? number_format((float)$dev['latest_value'], 2) . ' ' . $dev['latest_unit'] : '—' ?>
                                        </span>
                                        <span class="time text-dim" id="tableTime-<?= $dev['id'] ?>">
                                            <?= isset($dev['latest_timestamp']) ? substr($dev['latest_timestamp'], 11, 8) : 'Pending' ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <button class="btn btn-secondary btn-xs toggle-device-btn" data-id="<?= $dev['id'] ?>" id="btnTableToggle-<?= $dev['id'] ?>">
                                            <?= $dev['status'] === 'offline' ? 'Online' : 'Offline' ?>
                                        </button>
                                        <button class="btn btn-secondary btn-xs btn-inject-telemetry" data-id="<?= $dev['id'] ?>" data-name="<?= htmlspecialchars($dev['name'], ENT_QUOTES, 'UTF-8') ?>" data-type="<?= htmlspecialchars($dev['type'], ENT_QUOTES, 'UTF-8') ?>">
                                            Inject
                                        </button>
                                        <button class="btn btn-danger btn-xs btn-delete-device" data-id="<?= $dev['id'] ?>" data-name="<?= htmlspecialchars($dev['name'], ENT_QUOTES, 'UTF-8') ?>">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Provision New Sensor -->
<div class="modal-backdrop" id="addDeviceModal" style="display: none;">
    <div class="modal-card glass-card">
        <div class="modal-header">
            <h3 class="modal-title">PROVISION NEW SENSOR NODE</h3>
            <button class="modal-close-btn" id="btnCloseAddDeviceModal">&times;</button>
        </div>
        <form id="formAddDevice" class="modal-form">
            <div class="form-group">
                <label for="devNameInput" class="form-label">Sensor Node Identifier / Name</label>
                <input type="text" id="devNameInput" class="form-control" placeholder="e.g. Auxiliary Thermal Loop Sensor 3" required>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label for="devTypeSelect" class="form-label">Physical Sensor Type</label>
                    <select id="devTypeSelect" class="form-control" required>
                        <option value="temperature">Temperature (°C)</option>
                        <option value="pressure">Pressure (bar)</option>
                        <option value="radiation">Radiation (µSv/h)</option>
                        <option value="humidity">Humidity (%RH)</option>
                        <option value="vibration">Vibration (mm/s)</option>
                        <option value="voltage">Voltage (V)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="devStatusSelect" class="form-label">Initial State</label>
                    <select id="devStatusSelect" class="form-control" required>
                        <option value="online">Online (Active Physics Simulation)</option>
                        <option value="offline">Offline (Standby)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="devLocationInput" class="form-label">Installation Zone / Location</label>
                <input type="text" id="devLocationInput" class="form-control" placeholder="e.g. Sector 4-B Turbine Manifold" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnCancelAddDevice">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitAddDevice">Deploy Sensor Node</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Inject Telemetry -->
<div class="modal-backdrop" id="injectModal" style="display: none;">
    <div class="modal-card glass-card">
        <div class="modal-header">
            <h3 class="modal-title">MANUAL TELEMETRY INJECTION</h3>
            <button class="modal-close-btn" id="btnCloseInjectModal">&times;</button>
        </div>
        <form id="formInject" class="modal-form">
            <input type="hidden" id="injectDeviceId">
            <div class="form-group">
                <label class="form-label">Target Node</label>
                <input type="text" id="injectDeviceName" class="form-control" readonly style="opacity: 0.8;">
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label for="injectMetric" class="form-label">Metric Type</label>
                    <input type="text" id="injectMetric" class="form-control" readonly style="opacity: 0.8;">
                </div>
                <div class="form-group">
                    <label for="injectValue" class="form-label">Telemetry Value</label>
                    <input type="number" step="0.01" id="injectValue" class="form-control" placeholder="e.g. 95.5" required>
                </div>
            </div>
            <p class="text-dim" style="font-size: 0.85rem; margin-top: -0.5rem; margin-bottom: 1rem;">
                Tip: Enter high values to test automated safety rules (e.g., Temp &gt; 82°C, Rad &gt; 0.65 µSv/h).
            </p>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnCancelInject">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitInject">Transmit Telemetry Point</button>
            </div>
        </form>
    </div>
</div>
