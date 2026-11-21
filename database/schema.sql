-- OmniMesh IoT & Telemetry Database Schema (SQLite)

PRAGMA foreign_keys = ON;

-- 1. Devices Table
CREATE TABLE IF NOT EXISTS devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    type TEXT NOT NULL, -- 'radiation', 'temperature', 'pressure', 'humidity', 'vibration', 'voltage'
    status TEXT NOT NULL DEFAULT 'online', -- 'online', 'warning', 'critical', 'offline'
    location TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Telemetry Logs Table
CREATE TABLE IF NOT EXISTS telemetry_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id INTEGER NOT NULL,
    metric TEXT NOT NULL,
    value REAL NOT NULL,
    unit TEXT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(device_id) REFERENCES devices(id) ON DELETE CASCADE
);

-- 3. Automation Rules Table
CREATE TABLE IF NOT EXISTS rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    condition_metric TEXT NOT NULL, -- 'temperature', 'pressure', 'radiation', 'humidity', 'vibration', 'voltage'
    operator TEXT NOT NULL, -- '>', '<', '>=', '<=', '==', '!='
    threshold REAL NOT NULL,
    action TEXT NOT NULL, -- 'TRIGGER_COOLING', 'VENT_PRESSURE', 'STATUS_CRITICAL', 'STATUS_WARNING', 'ALERT_LOG', 'SYSTEM_SHUTDOWN'
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 4. Incident / Trigger Audit Logs Table
CREATE TABLE IF NOT EXISTS incidents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rule_id INTEGER,
    device_id INTEGER,
    metric TEXT NOT NULL,
    value REAL NOT NULL,
    message TEXT NOT NULL,
    severity TEXT NOT NULL DEFAULT 'warning', -- 'info', 'warning', 'critical'
    triggered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(rule_id) REFERENCES rules(id) ON DELETE SET NULL,
    FOREIGN KEY(device_id) REFERENCES devices(id) ON DELETE CASCADE
);

-- Indices for performance
CREATE INDEX IF NOT EXISTS idx_telemetry_device_time ON telemetry_logs(device_id, timestamp);
CREATE INDEX IF NOT EXISTS idx_telemetry_metric ON telemetry_logs(metric, timestamp);
CREATE INDEX IF NOT EXISTS idx_incidents_time ON incidents(triggered_at);

-- Initial Seed Data: Baseline Devices
INSERT OR IGNORE INTO devices (id, name, type, status, location) VALUES
(1, 'Reactor Core Alpha Monitor', 'radiation', 'online', 'Sector 7-G (Containment Vault)'),
(2, 'Cryogenic Coolant Sensor B', 'temperature', 'online', 'Bay 04 (Sub-Zero Loop)'),
(3, 'Turbine Primary Chamber', 'pressure', 'online', 'Sub-level 2 (Power Plant)'),
(4, 'Atmospheric Array Cleanroom', 'humidity', 'online', 'Fabrication Lab 3'),
(5, 'Substation Power Feeder', 'voltage', 'online', 'Grid Substation North'),
(6, 'Central Gyro Transducer', 'vibration', 'online', 'Rotor Pod Alpha');

-- Initial Seed Data: Automation Rules
INSERT OR IGNORE INTO rules (id, name, condition_metric, operator, threshold, action, is_active) VALUES
(1, 'High Thermal Overheat Alert', 'temperature', '>', 82.0, 'TRIGGER_COOLING', 1),
(2, 'Pressure Relief Boundary Exceeded', 'pressure', '>', 12.0, 'VENT_PRESSURE', 1),
(3, 'Containment Gamma Spike Hazard', 'radiation', '>', 0.65, 'STATUS_CRITICAL', 1),
(4, 'Cleanroom Humidity Excursion', 'humidity', '>', 70.0, 'STATUS_WARNING', 1),
(5, 'Grid Voltage Sag Protection', 'voltage', '<', 210.0, 'ALERT_LOG', 1),
(6, 'Excessive Vibration Resonance', 'vibration', '>', 6.5, 'STATUS_WARNING', 1);
