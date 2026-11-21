/**
 * OmniMesh IoT & Telemetry Orchestrator - Client Application Engine
 * Pure Vanilla JavaScript (ES6+), Server-Sent Events (SSE), Canvas Graphics & Web Audio API
 */

(function () {
    'use strict';

    // --------------------------------------------------------------------------
    // 1. Audio Notification Synthesizer (Web Audio API)
    // --------------------------------------------------------------------------
    class AudioAlertEngine {
        constructor() {
            this.audioCtx = null;
            this.muted = localStorage.getItem('omnimesh_muted') === 'true';
            this.updateButtonUI();
        }

        initContext() {
            if (!this.audioCtx) {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (AudioContext) {
                    this.audioCtx = new AudioContext();
                }
            }
            if (this.audioCtx && this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }
        }

        toggleMute() {
            this.muted = !this.muted;
            localStorage.setItem('omnimesh_muted', String(this.muted));
            this.updateButtonUI();
            if (!this.muted) {
                this.playPulseTone();
            }
        }

        updateButtonUI() {
            const btn = document.getElementById('btnAudioToggle');
            const icon = document.getElementById('audioIcon');
            if (!btn || !icon) return;

            if (this.muted) {
                btn.style.opacity = '0.5';
                btn.title = 'Audio Alarms Muted (Click to Unmute)';
                icon.innerHTML = '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><line x1="23" y1="9" x2="17" y2="15"></line><line x1="17" y1="9" x2="23" y2="15"></line>';
            } else {
                btn.style.opacity = '1';
                btn.title = 'Audio Alarms Active (Click to Mute)';
                icon.innerHTML = '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>';
            }
        }

        playPulseTone() {
            if (this.muted) return;
            try {
                this.initContext();
                if (!this.audioCtx) return;

                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, this.audioCtx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(440, this.audioCtx.currentTime + 0.08);

                gain.gain.setValueAtTime(0.04, this.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + 0.08);

                osc.connect(gain);
                gain.connect(this.audioCtx.destination);
                osc.start();
                osc.stop(this.audioCtx.currentTime + 0.08);
            } catch (e) {
                // AudioContext restrictions
            }
        }

        playIncidentAlarm(severity = 'warning') {
            if (this.muted) return;
            try {
                this.initContext();
                if (!this.audioCtx) return;

                const now = this.audioCtx.currentTime;
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();

                if (severity === 'critical') {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(520, now);
                    osc.frequency.linearRampToValueAtTime(260, now + 0.22);
                    gain.gain.setValueAtTime(0.12, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.22);
                    osc.start(now);
                    osc.stop(now + 0.22);
                } else {
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(740, now);
                    osc.frequency.linearRampToValueAtTime(880, now + 0.15);
                    gain.gain.setValueAtTime(0.07, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
                    osc.start(now);
                    osc.stop(now + 0.15);
                }

                osc.connect(gain);
                gain.connect(this.audioCtx.destination);
            } catch (e) {
                // Audio Context may require first user gesture
            }
        }
    }

    const audioEngine = new AudioAlertEngine();

    // --------------------------------------------------------------------------
    // 2. High-DPI HTML5 Canvas Radial Dial Gauge Class
    // --------------------------------------------------------------------------
    class RadialGauge {
        constructor(canvasId, options = {}) {
            this.canvas = document.getElementById(canvasId);
            if (!this.canvas) return;
            this.ctx = this.canvas.getContext('2d');

            this.min = options.min ?? 0;
            this.max = options.max ?? 100;
            this.val = options.initial ?? this.min;
            this.targetVal = this.val;
            this.unit = options.unit ?? '';
            this.title = options.title ?? '';
            this.threshold = options.threshold ?? null;
            this.primaryColor = options.primaryColor ?? '#00f0ff';
            this.dangerColor = options.dangerColor ?? '#ef4444';
            this.warningColor = options.warningColor ?? '#f59e0b';

            // High DPI resolution setup
            this.setupDPI();
            this.animate = this.animate.bind(this);
            this.animating = false;
            this.render();
        }

        setupDPI() {
            const dpr = window.devicePixelRatio || 1;
            const rect = this.canvas.getBoundingClientRect();
            const width = rect.width > 0 ? rect.width : (this.canvas.width || 220);
            const height = rect.height > 0 ? rect.height : (this.canvas.height || 180);

            this.canvas.width = width * dpr;
            this.canvas.height = height * dpr;
            this.ctx.scale(dpr, dpr);
            this.displayW = width;
            this.displayH = height;
        }

        setValue(newVal) {
            this.targetVal = Math.max(this.min, Math.min(this.max, newVal));
            if (!this.animating) {
                this.animating = true;
                requestAnimationFrame(this.animate);
            }
        }

        animate() {
            const delta = this.targetVal - this.val;
            if (Math.abs(delta) < 0.005) {
                this.val = this.targetVal;
                this.animating = false;
                this.render();
                return;
            }

            this.val += delta * 0.18; // Smooth damping
            this.render();
            requestAnimationFrame(this.animate);
        }

        render() {
            if (!this.ctx) return;
            const ctx = this.ctx;
            const w = this.displayW;
            const h = this.displayH;
            const cx = w / 2;
            const cy = h * 0.72;
            const radius = Math.min(w * 0.42, h * 0.58);

            ctx.clearRect(0, 0, w, h);

            const startAngle = Math.PI * 0.8;
            const endAngle = Math.PI * 2.2;
            const totalAngle = endAngle - startAngle;

            // 1. Background Arc Track
            ctx.beginPath();
            ctx.arc(cx, cy, radius, startAngle, endAngle);
            ctx.lineWidth = 10;
            ctx.lineCap = 'round';
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.06)';
            ctx.stroke();

            // 2. Ticks
            const tickCount = 12;
            for (let i = 0; i <= tickCount; i++) {
                const angle = startAngle + (totalAngle * (i / tickCount));
                const innerR = (i % 3 === 0) ? radius - 16 : radius - 11;
                const outerR = radius - 5;
                const x1 = cx + Math.cos(angle) * innerR;
                const y1 = cy + Math.sin(angle) * innerR;
                const x2 = cx + Math.cos(angle) * outerR;
                const y2 = cy + Math.sin(angle) * outerR;

                ctx.beginPath();
                ctx.moveTo(x1, y1);
                ctx.lineTo(x2, y2);
                ctx.lineWidth = (i % 3 === 0) ? 2 : 1;
                ctx.strokeStyle = (i % 3 === 0) ? 'rgba(255, 255, 255, 0.35)' : 'rgba(255, 255, 255, 0.12)';
                ctx.stroke();
            }

            // 3. Active Value Arc
            const pct = Math.max(0, Math.min(1, (this.val - this.min) / (this.max - this.min)));
            const currentAngle = startAngle + (totalAngle * pct);

            ctx.beginPath();
            ctx.arc(cx, cy, radius, startAngle, currentAngle);
            ctx.lineWidth = 10;
            ctx.lineCap = 'round';

            // Dynamic color based on thresholds
            let strokeColor = this.primaryColor;
            if (this.threshold !== null && this.val >= this.threshold) {
                strokeColor = this.dangerColor;
            } else if (pct > 0.75) {
                strokeColor = this.warningColor;
            }

            ctx.strokeStyle = strokeColor;
            ctx.shadowColor = strokeColor;
            ctx.shadowBlur = 10;
            ctx.stroke();
            ctx.shadowBlur = 0; // Reset shadow

            // 4. Center Needle
            ctx.save();
            ctx.translate(cx, cy);
            ctx.rotate(currentAngle);

            // Needle polygon
            ctx.beginPath();
            ctx.moveTo(-radius * 0.15, -2);
            ctx.lineTo(radius * 0.85, 0);
            ctx.lineTo(-radius * 0.15, 2);
            ctx.closePath();
            ctx.fillStyle = strokeColor;
            ctx.shadowColor = strokeColor;
            ctx.shadowBlur = 8;
            ctx.fill();
            ctx.restore();

            // 5. Center Pivot Cap
            ctx.beginPath();
            ctx.arc(cx, cy, 6, 0, Math.PI * 2);
            ctx.fillStyle = '#fff';
            ctx.fill();

            ctx.beginPath();
            ctx.arc(cx, cy, 9, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.4)';
            ctx.lineWidth = 2;
            ctx.stroke();
        }
    }

    // --------------------------------------------------------------------------
    // 3. Multi-Series Oscilloscope Rolling Chart Class
    // --------------------------------------------------------------------------
    class OscilloscopeChart {
        constructor(canvasId) {
            this.canvas = document.getElementById(canvasId);
            if (!this.canvas) return;
            this.ctx = this.canvas.getContext('2d');

            this.series = {
                temperature: { label: 'Temp (°C)', color: '#10b981', visible: true, data: [], min: 0, max: 100 },
                pressure: { label: 'Pressure (bar)', color: '#f59e0b', visible: true, data: [], min: 0, max: 16 },
                humidity: { label: 'Humidity (%RH)', color: '#8b5cf6', visible: true, data: [], min: 0, max: 100 },
                radiation: { label: 'Rad (µSv/h)', color: '#00f0ff', visible: true, data: [], min: 0, max: 1.0 },
            };

            this.maxPoints = 25;
            this.setupDPI();
            window.addEventListener('resize', () => {
                this.setupDPI();
                this.render();
            });
        }

        setupDPI() {
            const dpr = window.devicePixelRatio || 1;
            const rect = this.canvas.getBoundingClientRect();
            const width = rect.width > 0 ? rect.width : (this.canvas.width || 800);
            const height = rect.height > 0 ? rect.height : (this.canvas.height || 280);

            this.canvas.width = width * dpr;
            this.canvas.height = height * dpr;
            this.ctx.scale(dpr, dpr);
            this.w = width;
            this.h = height;
        }

        loadInitialData(initialSeries) {
            if (!initialSeries) return;

            for (const [metric, points] of Object.entries(initialSeries)) {
                if (this.series[metric] && Array.isArray(points)) {
                    this.series[metric].data = points.map(p => ({
                        time: p.time_label || (p.timestamp ? p.timestamp.substring(11, 19) : ''),
                        val: parseFloat(p.value),
                    }));
                }
            }
            this.render();
        }

        pushPoint(metric, val, timeLabel) {
            if (!this.series[metric]) return;

            this.series[metric].data.push({
                time: timeLabel || new Date().toISOString().substring(11, 19),
                val: parseFloat(val),
            });

            if (this.series[metric].data.length > this.maxPoints) {
                this.series[metric].data.shift();
            }

            this.render();
        }

        toggleSeries(metric) {
            if (this.series[metric]) {
                this.series[metric].visible = !this.series[metric].visible;
                this.render();
                return this.series[metric].visible;
            }
            return false;
        }

        render() {
            if (!this.ctx) return;
            const ctx = this.ctx;
            const w = this.w;
            const h = this.h;
            const padding = { top: 20, right: 20, bottom: 35, left: 45 };
            const plotW = w - padding.left - padding.right;
            const plotH = h - padding.top - padding.bottom;

            ctx.clearRect(0, 0, w, h);

            // 1. Grid lines (horizontal)
            const gridRows = 4;
            ctx.font = '10px "JetBrains Mono", monospace';
            ctx.fillStyle = 'rgba(255, 255, 255, 0.3)';
            ctx.textAlign = 'right';

            for (let r = 0; r <= gridRows; r++) {
                const y = padding.top + (plotH * (r / gridRows));
                ctx.beginPath();
                ctx.moveTo(padding.left, y);
                ctx.lineTo(w - padding.right, y);
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
                ctx.lineWidth = 1;
                ctx.stroke();

                const pct = 100 - (r * 25);
                ctx.fillText(`${pct}%`, padding.left - 8, y + 3);
            }

            // 2. Vertical grid lines & time labels
            const gridCols = 5;
            ctx.textAlign = 'center';
            for (let c = 0; c <= gridCols; c++) {
                const x = padding.left + (plotW * (c / gridCols));
                ctx.beginPath();
                ctx.moveTo(x, padding.top);
                ctx.lineTo(x, h - padding.bottom);
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.04)';
                ctx.stroke();
            }

            // Draw each active series
            for (const [key, s] of Object.entries(this.series)) {
                if (!s.visible || s.data.length === 0) continue;

                const points = s.data;
                const stepX = plotW / Math.max(1, this.maxPoints - 1);
                const startIndex = this.maxPoints - points.length;

                // Path coordinates
                const coords = [];
                for (let i = 0; i < points.length; i++) {
                    const x = padding.left + ((startIndex + i) * stepX);
                    const normVal = (points[i].val - s.min) / (s.max - s.min);
                    const clampedNorm = Math.max(0, Math.min(1, normVal));
                    const y = padding.top + (plotH * (1 - clampedNorm));
                    coords.push({ x, y });
                }

                // Gradient Area Fill under line
                if (coords.length > 1) {
                    const grad = ctx.createLinearGradient(0, padding.top, 0, h - padding.bottom);
                    grad.addColorStop(0, hexToRgba(s.color, 0.18));
                    grad.addColorStop(1, hexToRgba(s.color, 0.0));

                    ctx.beginPath();
                    ctx.moveTo(coords[0].x, h - padding.bottom);
                    for (const pt of coords) {
                        ctx.lineTo(pt.x, pt.y);
                    }
                    ctx.lineTo(coords[coords.length - 1].x, h - padding.bottom);
                    ctx.closePath();
                    ctx.fillStyle = grad;
                    ctx.fill();
                }

                // Smooth Curve Line
                ctx.beginPath();
                ctx.moveTo(coords[0].x, coords[0].y);
                for (let i = 1; i < coords.length; i++) {
                    // Smooth quadratic curve midpoint
                    const xc = (coords[i].x + coords[i - 1].x) / 2;
                    const yc = (coords[i].y + coords[i - 1].y) / 2;
                    ctx.quadraticCurveTo(coords[i - 1].x, coords[i - 1].y, xc, yc);
                }
                if (coords.length > 1) {
                    ctx.lineTo(coords[coords.length - 1].x, coords[coords.length - 1].y);
                }

                ctx.strokeStyle = s.color;
                ctx.lineWidth = 2;
                ctx.shadowColor = s.color;
                ctx.shadowBlur = 6;
                ctx.stroke();
                ctx.shadowBlur = 0;

                // Glowing pulsing marker at the latest point
                if (coords.length > 0) {
                    const last = coords[coords.length - 1];
                    ctx.beginPath();
                    ctx.arc(last.x, last.y, 4, 0, Math.PI * 2);
                    ctx.fillStyle = '#fff';
                    ctx.fill();
                    ctx.beginPath();
                    ctx.arc(last.x, last.y, 7, 0, Math.PI * 2);
                    ctx.strokeStyle = s.color;
                    ctx.lineWidth = 2;
                    ctx.stroke();
                }
            }
        }
    }

    function hexToRgba(hex, alpha) {
        let c;
        if (/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)) {
            c = hex.substring(1).split('');
            if (c.length === 3) {
                c = [c[0], c[0], c[1], c[1], c[2], c[2]];
            }
            c = '0x' + c.join('');
            return `rgba(${[(c >> 16) & 255, (c >> 8) & 255, c & 255].join(',')},${alpha})`;
        }
        return `rgba(0,240,255,${alpha})`;
    }

    // --------------------------------------------------------------------------
    // 4. Toast Notification Dispatcher
    // --------------------------------------------------------------------------
    function showToast(title, desc, severity = 'info') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${severity}`;
        toast.innerHTML = `
            <div class="toast-body">
                <div class="toast-title">${escapeHTML(title)}</div>
                <div class="toast-desc">${escapeHTML(desc)}</div>
            </div>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    function escapeHTML(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // --------------------------------------------------------------------------
    // 5. System Clock (UTC Live)
    // --------------------------------------------------------------------------
    function startClock() {
        const el = document.getElementById('systemClock');
        if (!el) return;

        function update() {
            const now = new Date();
            const h = String(now.getUTCHours()).padStart(2, '0');
            const m = String(now.getUTCMinutes()).padStart(2, '0');
            const s = String(now.getUTCSeconds()).padStart(2, '0');
            el.textContent = `${h}:${m}:${s} UTC`;
        }

        update();
        setInterval(update, 1000);
    }

    // --------------------------------------------------------------------------
    // 6. Real-time Telemetry Manager (SSE Connection & UI Updates)
    // --------------------------------------------------------------------------
    let gauges = {};
    let oscChart = null;

    function initDashboardComponents() {
        // Init Canvas Dial Gauges
        if (document.getElementById('gaugeRadiation')) {
            gauges.radiation = new RadialGauge('gaugeRadiation', {
                min: 0.0,
                max: 1.0,
                unit: 'µSv/h',
                threshold: 0.65,
                primaryColor: '#00f0ff',
                dangerColor: '#ef4444',
            });
        }

        if (document.getElementById('gaugeTemperature')) {
            gauges.temperature = new RadialGauge('gaugeTemperature', {
                min: 0.0,
                max: 100.0,
                unit: '°C',
                threshold: 82.0,
                primaryColor: '#10b981',
                dangerColor: '#ef4444',
            });
        }

        if (document.getElementById('gaugePressure')) {
            gauges.pressure = new RadialGauge('gaugePressure', {
                min: 0.0,
                max: 16.0,
                unit: 'bar',
                threshold: 12.0,
                primaryColor: '#f59e0b',
                dangerColor: '#ef4444',
            });
        }

        if (document.getElementById('gaugeHumidity')) {
            gauges.humidity = new RadialGauge('gaugeHumidity', {
                min: 0.0,
                max: 100.0,
                unit: '%RH',
                threshold: 70.0,
                primaryColor: '#8b5cf6',
                dangerColor: '#ef4444',
            });
        }

        // Init Waveform Oscilloscope Chart
        if (document.getElementById('oscilloscopeChart')) {
            oscChart = new OscilloscopeChart('oscilloscopeChart');

            const initSeriesElem = document.getElementById('initChartSeries');
            if (initSeriesElem) {
                try {
                    const parsed = JSON.parse(initSeriesElem.textContent);
                    oscChart.loadInitialData(parsed);
                } catch (e) {
                    console.error('Error loading initial chart series:', e);
                }
            }

            // Legend toggle chips
            document.querySelectorAll('.legend-chip').forEach(btn => {
                btn.addEventListener('click', () => {
                    const metric = btn.getAttribute('data-metric');
                    const isVisible = oscChart.toggleSeries(metric);
                    btn.classList.toggle('active', isVisible);
                });
            });
        }

        // Apply initial snapshot values to gauges & text readouts
        const initSnapElem = document.getElementById('initSnapshotData');
        if (initSnapElem) {
            try {
                const snap = JSON.parse(initSnapElem.textContent);
                updateDashboardSnapshot(snap);
            } catch (e) {
                console.error('Error loading initial snapshot:', e);
            }
        }
    }

    function updateDashboardSnapshot(snap) {
        if (!snap) return;

        if (snap.radiation && gauges.radiation) {
            gauges.radiation.setValue(snap.radiation.value);
            const el = document.getElementById('radReadout');
            if (el) el.textContent = Number(snap.radiation.value).toFixed(3);
        }

        if (snap.temperature && gauges.temperature) {
            gauges.temperature.setValue(snap.temperature.value);
            const el = document.getElementById('tempReadout');
            if (el) el.textContent = Number(snap.temperature.value).toFixed(1);
        }

        if (snap.pressure && gauges.pressure) {
            gauges.pressure.setValue(snap.pressure.value);
            const el = document.getElementById('pressReadout');
            if (el) el.textContent = Number(snap.pressure.value).toFixed(2);
        }

        if (snap.humidity && gauges.humidity) {
            gauges.humidity.setValue(snap.humidity.value);
            const el = document.getElementById('humidReadout');
            if (el) el.textContent = Number(snap.humidity.value).toFixed(1);
        }
    }

    function handleIncomingTelemetryPayload(payload) {
        if (!payload) return;

        // 1. Update Dial Gauges
        if (payload.snapshot) {
            updateDashboardSnapshot(payload.snapshot);
        }

        // 2. Feed Multi-Series Waveform Chart
        if (oscChart && payload.readings) {
            for (const r of payload.readings) {
                oscChart.pushPoint(r.metric, r.value, payload.time_label);
            }
        }

        // 3. Update KPI Counters
        if (payload.fleet_stats) {
            const kpiOnline = document.getElementById('kpiOnlineNodes');
            const kpiRate = document.getElementById('kpiActiveRate');
            if (kpiOnline) kpiOnline.textContent = `${payload.fleet_stats.online} / ${payload.fleet_stats.total}`;
            if (kpiRate) kpiRate.textContent = `${payload.fleet_stats.active_rate}%`;

            const fleetTotal = document.getElementById('fleetTotal');
            const fleetOnline = document.getElementById('fleetOnline');
            const fleetWarning = document.getElementById('fleetWarning');
            const fleetOffline = document.getElementById('fleetOffline');
            if (fleetTotal) fleetTotal.textContent = payload.fleet_stats.total;
            if (fleetOnline) fleetOnline.textContent = payload.fleet_stats.online;
            if (fleetWarning) fleetWarning.textContent = payload.fleet_stats.warning;
            if (fleetOffline) fleetOffline.textContent = payload.fleet_stats.offline;
        }

        if (payload.aggregates) {
            const kpiPoints = document.getElementById('kpiTotalPoints');
            const kpiInc = document.getElementById('kpiTotalIncidents');
            const kpiCrit = document.getElementById('kpiCriticalIncidents');
            if (kpiPoints) kpiPoints.textContent = Number(payload.aggregates.total_points).toLocaleString();
            if (kpiInc) kpiInc.textContent = payload.aggregates.total_incidents;
            if (kpiCrit) kpiCrit.textContent = payload.aggregates.critical_incidents;
        }

        // 4. Update Device Cards & Table Rows
        if (payload.readings) {
            for (const r of payload.readings) {
                // Card in Dashboard
                const devValEl = document.getElementById(`devVal-${r.device_id}`);
                if (devValEl) {
                    devValEl.textContent = `${Number(r.value).toFixed(2)} ${r.unit}`;
                }
                const devBadgeEl = document.getElementById(`devStatusBadge-${r.device_id}`);
                const devCardEl = document.getElementById(`devCard-${r.device_id}`);
                if (devBadgeEl && devCardEl) {
                    devBadgeEl.className = `status-indicator status-${r.status}`;
                    devBadgeEl.textContent = r.status.toUpperCase();
                    devCardEl.className = `glass-card device-item-card status-${r.status}`;
                }

                // Table row in Devices View
                const tableValEl = document.getElementById(`tableVal-${r.device_id}`);
                const tableTimeEl = document.getElementById(`tableTime-${r.device_id}`);
                const tableStatusEl = document.getElementById(`tableStatus-${r.device_id}`);
                if (tableValEl) tableValEl.textContent = `${Number(r.value).toFixed(2)} ${r.unit}`;
                if (tableTimeEl) tableTimeEl.textContent = payload.time_label;
                if (tableStatusEl) {
                    tableStatusEl.className = `status-indicator status-${r.status}`;
                    tableStatusEl.textContent = r.status.toUpperCase();
                }
            }
        }

        // 5. Handle Triggered Incidents
        if (payload.incidents && payload.incidents.length > 0) {
            const ticker = document.getElementById('incidentTickerStream');
            const emptyNotice = document.getElementById('tickerEmpty');
            if (emptyNotice) emptyNotice.remove();

            for (const inc of payload.incidents) {
                // Play audio tone
                audioEngine.playIncidentAlarm(inc.severity);

                // Show Toast popup
                showToast(
                    `BREACH: ${inc.rule_name || 'Safety Rule'}`,
                    inc.message,
                    inc.severity || 'warning'
                );

                // Append to ticker if present
                if (ticker) {
                    const item = document.createElement('div');
                    item.className = `ticker-item severity-${inc.severity}`;
                    item.innerHTML = `
                        <div class="ticker-item-meta">
                            <span class="badge badge-${inc.severity}">${(inc.severity || 'WARN').toUpperCase()}</span>
                            <span class="ticker-time">${inc.time_label || ''}</span>
                            <span class="ticker-device">${escapeHTML(inc.device_name || 'Sensor Node')}</span>
                        </div>
                        <div class="ticker-message">${escapeHTML(inc.message)}</div>
                        <div class="ticker-action">Action: <code>${escapeHTML(inc.action || 'ALERT_LOG')}</code></div>
                    `;
                    ticker.insertBefore(item, ticker.firstChild);

                    // Cap ticker to 30 items
                    while (ticker.children.length > 30) {
                        ticker.removeChild(ticker.lastChild);
                    }
                }
            }
        }
    }

    // Server-Sent Events Connection Controller
    let eventSource = null;
    let reconnectTimeout = null;

    function connectSSE() {
        if (eventSource) {
            eventSource.close();
        }

        const pill = document.getElementById('connectionStatusPill');
        const text = document.getElementById('connectionStatusText');

        if (pill && text) {
            pill.className = 'connection-status-pill status-reconnecting';
            text.textContent = 'CONNECTING SSE...';
        }

        eventSource = new EventSource('/api/stream');

        eventSource.addEventListener('handshake', () => {
            if (pill && text) {
                pill.className = 'connection-status-pill status-connected';
                text.textContent = 'SSE CONNECTED';
            }
        });

        eventSource.addEventListener('telemetry', (e) => {
            if (pill && text && pill.classList.contains('status-reconnecting')) {
                pill.className = 'connection-status-pill status-connected';
                text.textContent = 'SSE CONNECTED';
            }
            try {
                const data = JSON.parse(e.data);
                handleIncomingTelemetryPayload(data);
            } catch (err) {
                console.error('Error parsing SSE telemetry event:', err);
            }
        });

        eventSource.onerror = () => {
            if (pill && text) {
                pill.className = 'connection-status-pill status-reconnecting';
                text.textContent = 'RECONNECTING...';
            }
            eventSource.close();
            clearTimeout(reconnectTimeout);
            reconnectTimeout = setTimeout(connectSSE, 3000);
        };
    }

    // --------------------------------------------------------------------------
    // 7. Interactive Actions & Event Handlers
    // --------------------------------------------------------------------------
    function initActionListeners() {
        // Audio Toggle Button
        const audioBtn = document.getElementById('btnAudioToggle');
        if (audioBtn) {
            audioBtn.addEventListener('click', () => {
                audioEngine.toggleMute();
            });
        }

        // Manual Simulation Pulse Button
        const pulseBtn = document.getElementById('btnSimulatePulse');
        if (pulseBtn) {
            pulseBtn.addEventListener('click', async () => {
                pulseBtn.disabled = true;
                pulseBtn.style.transform = 'scale(0.9)';
                audioEngine.playPulseTone();

                try {
                    const res = await fetch('/api/telemetry/simulate-step');
                    const json = await res.json();
                    if (json.status === 'success' && json.data) {
                        handleIncomingTelemetryPayload(json.data);
                        showToast('Pulse Emitted', 'Instant telemetry wave calculated across active nodes.', 'info');
                    }
                } catch (e) {
                    console.error('Manual pulse failed:', e);
                } finally {
                    setTimeout(() => {
                        pulseBtn.disabled = false;
                        pulseBtn.style.transform = 'none';
                    }, 300);
                }
            });
        }

        // Clear Ticker Button
        const clearTickerBtn = document.getElementById('btnClearTicker');
        if (clearTickerBtn) {
            clearTickerBtn.addEventListener('click', () => {
                const ticker = document.getElementById('incidentTickerStream');
                if (ticker) {
                    ticker.innerHTML = '<div class="ticker-empty" id="tickerEmpty">Incident stream cleared by operator.</div>';
                }
            });
        }

        // Mobile Sidebar Drawer
        const menuBtn = document.getElementById('mobileMenuBtn');
        const closeBtn = document.getElementById('mobileCloseBtn');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');

        function toggleSidebar(open) {
            if (sidebar) sidebar.classList.toggle('open', open);
            if (backdrop) backdrop.classList.toggle('active', open);
        }

        if (menuBtn) menuBtn.addEventListener('click', () => toggleSidebar(true));
        if (closeBtn) closeBtn.addEventListener('click', () => toggleSidebar(false));
        if (backdrop) backdrop.addEventListener('click', () => toggleSidebar(false));

        // Device Toggle Status (Online / Offline)
        document.addEventListener('click', async (e) => {
            const toggleBtn = e.target.closest('.toggle-device-btn');
            if (toggleBtn) {
                const id = toggleBtn.getAttribute('data-id');
                toggleBtn.disabled = true;

                try {
                    const res = await fetch(`/api/devices/${id}/toggle`, { method: 'POST' });
                    const json = await res.json();
                    if (json.status === 'success') {
                        const newStatus = json.new_status;
                        showToast('Node Power Toggled', json.message, 'info');

                        // Update buttons
                        toggleBtn.textContent = (newStatus === 'offline') ? 'Power ON' : 'Deactivate';

                        // Update Card in Dashboard if present
                        const card = document.getElementById(`devCard-${id}`);
                        const badge = document.getElementById(`devStatusBadge-${id}`);
                        if (card) card.className = `glass-card device-item-card status-${newStatus}`;
                        if (badge) {
                            badge.className = `status-indicator status-${newStatus}`;
                            badge.textContent = newStatus.toUpperCase();
                        }

                        // Update Table in Devices if present
                        const tblBadge = document.getElementById(`tableStatus-${id}`);
                        if (tblBadge) {
                            tblBadge.className = `status-indicator status-${newStatus}`;
                            tblBadge.textContent = newStatus.toUpperCase();
                        }
                    }
                } catch (err) {
                    console.error('Toggle failed:', err);
                } finally {
                    toggleBtn.disabled = false;
                }
            }
        });

        // Delete Device Node
        document.addEventListener('click', async (e) => {
            const delBtn = e.target.closest('.btn-delete-device');
            if (delBtn) {
                const id = delBtn.getAttribute('data-id');
                const name = delBtn.getAttribute('data-name') || `Node #${id}`;

                if (confirm(`Are you sure you want to decommission and purge ${name}?`)) {
                    delBtn.disabled = true;
                    try {
                        const res = await fetch(`/api/devices/${id}/delete`, { method: 'POST' });
                        const json = await res.json();
                        if (json.status === 'success') {
                            showToast('Node Purged', json.message, 'warning');
                            const row = document.getElementById(`row-dev-${id}`);
                            if (row) row.remove();
                            const card = document.getElementById(`devCard-${id}`);
                            if (card) card.remove();
                        }
                    } catch (err) {
                        console.error('Delete failed:', err);
                    }
                }
            }
        });

        // Provision New Device Modal
        const addDevModal = document.getElementById('addDeviceModal');
        const openAddDevBtn = document.getElementById('btnOpenAddDeviceModal');
        const closeAddDevBtn = document.getElementById('btnCloseAddDeviceModal');
        const cancelAddDevBtn = document.getElementById('btnCancelAddDevice');
        const formAddDevice = document.getElementById('formAddDevice');

        function toggleAddModal(open) {
            if (addDevModal) addDevModal.style.display = open ? 'flex' : 'none';
        }

        if (openAddDevBtn) openAddDevBtn.addEventListener('click', () => toggleAddModal(true));
        if (closeAddDevBtn) closeAddDevBtn.addEventListener('click', () => toggleAddModal(false));
        if (cancelAddDevBtn) cancelAddDevBtn.addEventListener('click', () => toggleAddModal(false));

        if (formAddDevice) {
            formAddDevice.addEventListener('submit', async (e) => {
                e.preventDefault();
                const name = document.getElementById('devNameInput').value;
                const type = document.getElementById('devTypeSelect').value;
                const status = document.getElementById('devStatusSelect').value;
                const location = document.getElementById('devLocationInput').value;
                const submitBtn = document.getElementById('btnSubmitAddDevice');

                submitBtn.disabled = true;
                try {
                    const res = await fetch('/api/devices', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ name, type, status, location }),
                    });
                    const json = await res.json();
                    if (json.status === 'success') {
                        showToast('Sensor Deployed', json.message, 'success');
                        toggleAddModal(false);
                        formAddDevice.reset();
                        setTimeout(() => location.reload(), 500);
                    } else {
                        alert(json.message || 'Creation failed');
                    }
                } catch (err) {
                    console.error('Provision failed:', err);
                } finally {
                    submitBtn.disabled = false;
                }
            });
        }

        // Manual Telemetry Injection Modal
        const injectModal = document.getElementById('injectModal');
        const closeInjectBtn = document.getElementById('btnCloseInjectModal');
        const cancelInjectBtn = document.getElementById('btnCancelInject');
        const formInject = document.getElementById('formInject');

        function toggleInjectModal(open) {
            if (injectModal) injectModal.style.display = open ? 'flex' : 'none';
        }

        if (closeInjectBtn) closeInjectBtn.addEventListener('click', () => toggleInjectModal(false));
        if (cancelInjectBtn) cancelInjectBtn.addEventListener('click', () => toggleInjectModal(false));

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-inject-telemetry');
            if (btn) {
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                const type = btn.getAttribute('data-type');

                document.getElementById('injectDeviceId').value = id;
                document.getElementById('injectDeviceName').value = name;
                document.getElementById('injectMetric').value = type;
                document.getElementById('injectValue').value = '';

                toggleInjectModal(true);
            }
        });

        if (formInject) {
            formInject.addEventListener('submit', async (e) => {
                e.preventDefault();
                const device_id = parseInt(document.getElementById('injectDeviceId').value, 10);
                const metric = document.getElementById('injectMetric').value;
                const value = parseFloat(document.getElementById('injectValue').value);
                const submitBtn = document.getElementById('btnSubmitInject');

                submitBtn.disabled = true;
                try {
                    const res = await fetch('/api/telemetry/ingest', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ device_id, metric, value }),
                    });
                    const json = await res.json();
                    if (json.status === 'success') {
                        showToast('Telemetry Ingested', json.message, 'info');
                        toggleInjectModal(false);
                        audioEngine.playPulseTone();

                        // If rule incident was generated
                        if (json.data && json.data.incidents && json.data.incidents.length > 0) {
                            for (const inc of json.data.incidents) {
                                audioEngine.playIncidentAlarm(inc.severity);
                                showToast(`BREACH: ${inc.rule_name}`, inc.message, inc.severity);
                            }
                        }
                    } else {
                        alert(json.message || 'Ingest failed');
                    }
                } catch (err) {
                    console.error('Ingest error:', err);
                } finally {
                    submitBtn.disabled = false;
                }
            });
        }

        // Device Search & Filter in Devices Table
        const searchInput = document.getElementById('deviceSearchInput');
        const typeFilter = document.getElementById('deviceTypeFilter');

        function filterDevicesTable() {
            if (!searchInput && !typeFilter) return;
            const query = (searchInput?.value || '').toLowerCase();
            const filterType = typeFilter?.value || 'all';
            const rows = document.querySelectorAll('#devicesTableBody tr');

            rows.forEach(row => {
                const rowType = row.getAttribute('data-type') || '';
                const text = row.textContent.toLowerCase();
                const matchesQuery = text.includes(query);
                const matchesType = (filterType === 'all' || rowType === filterType);

                row.style.display = (matchesQuery && matchesType) ? '' : 'none';
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterDevicesTable);
        if (typeFilter) typeFilter.addEventListener('change', filterDevicesTable);

        // Automation Rule Builder Actions
        const formCreateRule = document.getElementById('formCreateRule');
        if (formCreateRule) {
            formCreateRule.addEventListener('submit', async (e) => {
                e.preventDefault();
                const name = document.getElementById('ruleName').value;
                const condition_metric = document.getElementById('ruleMetric').value;
                const operator = document.getElementById('ruleOperator').value;
                const threshold = parseFloat(document.getElementById('ruleThreshold').value);
                const action = document.getElementById('ruleAction').value;
                const submitBtn = document.getElementById('btnSubmitRule');

                submitBtn.disabled = true;
                try {
                    const res = await fetch('/api/rules', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ name, condition_metric, operator, threshold, action }),
                    });
                    const json = await res.json();
                    if (json.status === 'success') {
                        showToast('Policy Armed', json.message, 'success');
                        formCreateRule.reset();
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        alert(json.message || 'Rule creation failed');
                    }
                } catch (err) {
                    console.error('Create rule error:', err);
                } finally {
                    submitBtn.disabled = false;
                }
            });
        }

        // Toggle Rule Active State
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.rule-toggle-btn');
            if (btn) {
                const id = btn.getAttribute('data-id');
                btn.disabled = true;
                try {
                    const res = await fetch(`/api/rules/${id}/toggle`, { method: 'POST' });
                    const json = await res.json();
                    if (json.status === 'success') {
                        const isActive = json.is_active === 1;
                        btn.classList.toggle('active', isActive);
                        btn.textContent = isActive ? 'ACTIVE' : 'DISABLED';
                        showToast('Rule Status Changed', json.message, 'info');
                    }
                } catch (err) {
                    console.error('Toggle rule error:', err);
                } finally {
                    btn.disabled = false;
                }
            }
        });

        // Delete Rule
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-delete-rule');
            if (btn) {
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name') || `Rule #${id}`;

                if (confirm(`Purge automation rule "${name}"?`)) {
                    btn.disabled = true;
                    try {
                        const res = await fetch(`/api/rules/${id}/delete`, { method: 'POST' });
                        const json = await res.json();
                        if (json.status === 'success') {
                            showToast('Rule Purged', json.message, 'warning');
                            const row = document.getElementById(`rule-row-${id}`);
                            if (row) row.remove();
                        }
                    } catch (err) {
                        console.error('Delete rule error:', err);
                    }
                }
            }
        });

        // Refresh Audit Log in Rules View
        const refreshAuditBtn = document.getElementById('btnRefreshAudit');
        if (refreshAuditBtn) {
            refreshAuditBtn.addEventListener('click', async () => {
                refreshAuditBtn.disabled = true;
                try {
                    const res = await fetch('/api/rules/incidents');
                    const json = await res.json();
                    if (json.status === 'success' && json.data) {
                        const tbody = document.getElementById('incidentsTableBody');
                        if (tbody) {
                            if (json.data.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-dim" style="padding: 2rem;">No incident records found. System operating nominally.</td></tr>';
                            } else {
                                tbody.innerHTML = json.data.map(inc => `
                                    <tr>
                                        <td class="col-mono text-dim">${escapeHTML(inc.triggered_at)}</td>
                                        <td><span class="badge badge-${escapeHTML(inc.severity)}">${(inc.severity || 'WARN').toUpperCase()}</span></td>
                                        <td><strong>${escapeHTML(inc.device_name || 'Grid Node')}</strong></td>
                                        <td class="col-mono text-amber">${Number(inc.value).toFixed(2)} (${escapeHTML(inc.metric)})</td>
                                        <td>${escapeHTML(inc.rule_name || 'Custom Anomaly')}</td>
                                        <td class="text-dim">${escapeHTML(inc.message)}</td>
                                    </tr>
                                `).join('');
                            }
                        }
                        showToast('Audit Log Synced', 'Latest incident records retrieved from SQLite.', 'info');
                    }
                } catch (err) {
                    console.error('Audit refresh failed:', err);
                } finally {
                    refreshAuditBtn.disabled = false;
                }
            });
        }
    }

    // --------------------------------------------------------------------------
    // 8. Bootstrap Application on DOM Ready
    // --------------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        startClock();
        initDashboardComponents();
        initActionListeners();
        connectSSE();
    });

})();
