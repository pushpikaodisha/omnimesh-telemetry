<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="OmniMesh IoT & Telemetry Orchestrator - Real-time Physics & Rule Engine">
    <title><?= htmlspecialchars($title ?? 'OmniMesh IoT Orchestrator', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="theme-dark">
    <div class="mesh-app-shell">
        <!-- Sidebar Navigation -->
        <aside class="mesh-sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="/" class="brand-link">
                    <div class="brand-logo-hex">
                        <span class="hex-glow"></span>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                    </div>
                    <div class="brand-info">
                        <span class="brand-name">OMNIMESH</span>
                        <span class="brand-tag">ORCHESTRATOR v8.3</span>
                    </div>
                </a>
                <button class="mobile-close-btn" id="mobileCloseBtn" aria-label="Close navigation">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-label">MONITORING</div>
                <a href="/" class="nav-item <?= ($activeTab ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <svg class="nav-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="9"></rect>
                        <rect x="14" y="3" width="7" height="5"></rect>
                        <rect x="14" y="12" width="7" height="9"></rect>
                        <rect x="3" y="16" width="7" height="5"></rect>
                    </svg>
                    <span>Mission Control</span>
                    <span class="live-dot-pulse"></span>
                </a>

                <div class="nav-section-label">FLEET & RULES</div>
                <a href="/devices" class="nav-item <?= ($activeTab ?? '') === 'devices' ? 'active' : '' ?>">
                    <svg class="nav-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect>
                        <rect x="9" y="9" width="6" height="6"></rect>
                        <line x1="9" y1="1" x2="9" y2="4"></line>
                        <line x1="15" y1="1" x2="15" y2="4"></line>
                        <line x1="9" y1="20" x2="9" y2="23"></line>
                        <line x1="15" y1="20" x2="15" y2="23"></line>
                        <line x1="20" y1="9" x2="23" y2="9"></line>
                        <line x1="20" y1="14" x2="23" y2="14"></line>
                        <line x1="1" y1="9" x2="4" y2="9"></line>
                        <line x1="1" y1="14" x2="4" y2="14"></line>
                    </svg>
                    <span>Device Fleet</span>
                </a>

                <a href="/rules" class="nav-item <?= ($activeTab ?? '') === 'rules' ? 'active' : '' ?>">
                    <svg class="nav-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    <span>Automation Rules</span>
                </a>
            </nav>

            <div class="sidebar-status-box">
                <div class="status-row">
                    <span class="status-label">ENGINE:</span>
                    <span class="status-val text-cyan">PHP 8.3 OOP MVC</span>
                </div>
                <div class="status-row">
                    <span class="status-label">DATA STORE:</span>
                    <span class="status-val text-amber">SQLite WAL</span>
                </div>
                <div class="status-row">
                    <span class="status-label">STREAM:</span>
                    <span class="status-val text-emerald">SSE Real-Time</span>
                </div>
                <div class="progress-bar-wrap">
                    <div class="progress-bar-fill" id="gridHealthBar" style="width: 98%;"></div>
                </div>
            </div>
        </aside>

        <!-- Sidebar Backdrop for Mobile -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- Main Wrapper -->
        <div class="mesh-content-wrapper">
            <!-- Header Bar -->
            <header class="mesh-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle navigation menu">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    </button>
                    <div class="header-breadcrumbs">
                        <span class="crumb-root">GRID_NETWORK</span>
                        <span class="crumb-separator">/</span>
                        <span class="crumb-active"><?= strtoupper($activeTab ?? 'DASHBOARD') ?></span>
                    </div>
                </div>

                <div class="header-right">
                    <!-- Live Connection Status Pill -->
                    <div class="connection-status-pill status-connected" id="connectionStatusPill">
                        <span class="status-beacon"></span>
                        <span class="status-text" id="connectionStatusText">SSE CONNECTED</span>
                    </div>

                    <!-- UTC Live Clock -->
                    <div class="system-clock-box">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span id="systemClock" class="clock-mono">--:--:-- UTC</span>
                    </div>

                    <!-- Quick Action: Manual Pulse Step -->
                    <button class="btn btn-secondary btn-icon-only" id="btnSimulatePulse" title="Trigger Instant Simulation Pulse" aria-label="Trigger Simulation Pulse">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    </button>

                    <!-- Audio Alert Toggle -->
                    <button class="btn btn-secondary btn-icon-only" id="btnAudioToggle" title="Toggle Cyber Audio Alerts" aria-label="Toggle Sound Effects">
                        <svg id="audioIcon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                    </button>
                </div>
            </header>

            <!-- Main Dynamic Content Slot -->
            <main class="mesh-main-container">
                <?= $content ?? '' ?>
            </main>

            <!-- Footer -->
            <footer class="mesh-footer">
                <div class="footer-left">
                    <span>OMNIMESH TELEMETRY ORCHESTRATION PLATFORM &bull; AUTONOMOUS SENSOR MESH</span>
                </div>
                <div class="footer-right">
                    <span>STATUS: <strong class="text-emerald">OPTIMAL</strong></span>
                    <span class="sep">&bull;</span>
                    <span>REFRESH: <strong class="text-cyan">1500ms</strong></span>
                </div>
            </footer>
        </div>
    </div>

    <!-- Toast Notification Overlay -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="/assets/js/app.js"></script>
</body>
</html>
