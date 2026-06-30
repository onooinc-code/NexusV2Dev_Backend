<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page_title', 'Nexus Hub') — Nexus V2</title>

    <!-- Google Fonts: Inter + Outfit + JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- NProgress -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
    <!-- Nexus Design System -->
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body>

    <!-- ── Autopilot Warning Banner ── -->
    <div id="autopilot-banner" class="autopilot-warning-banner nx-autopilot-warning-pulse">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        ⚠️ SYSTEM AUTOPILOT ENGAGED GLOBALLY — ALL RESPONSES ARE AI-AUTOMATED
        <i class="fa-solid fa-triangle-exclamation ms-2"></i>
    </div>

    <!-- ── Global Loading Overlay ── -->
    <div id="nexus-global-loader">
        <div class="text-center">
            <div style="width: 48px; height: 48px; border: 3px solid rgba(59,130,246,0.2); border-top-color: hsl(217,91%,60%); border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px;"></div>
            <div class="loader-title" id="nexus-loader-text">Processing...</div>
            <div class="mt-3" style="width: 200px; height: 3px; background: rgba(255,255,255,0.08); border-radius: 2px; overflow: hidden;">
                <div id="nexus-loader-progress" style="height: 100%; background: linear-gradient(90deg, hsl(217,91%,60%), hsl(174,90%,41%)); width: 0%; transition: width 0.3s ease; border-radius: 2px;"></div>
            </div>
            <div class="mt-2 text-muted" style="font-size: 0.7rem; font-family: 'JetBrains Mono', monospace;" id="nexus-loader-sub">Please wait...</div>
        </div>
    </div>

    <div class="d-flex" id="wrapper">

        <!-- ═══════════════════════════════════════════════════
             SIDEBAR
        ═══════════════════════════════════════════════════ -->
        <div id="sidebar-wrapper">
            <!-- Brand Header -->
            <div class="sidebar-heading">
                <div class="brand-icon">
                    <i class="fa-solid fa-network-wired"></i>
                </div>
                <span>Nexus</span>
                <span class="brand-version">V2</span>
            </div>

            <!-- Navigation -->
            <div class="sidebar-nav list-group list-group-flush">

                <div class="nav-section-label">Core</div>

                <a href="{{ url('/hub/dashboard') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>NexusHub</span>
                </a>

                <div class="nav-section-label mt-2">People & Comms</div>

                <a href="{{ url('/hub/people-connect') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/people-connect') ? 'active' : '' }}">
                    <i class="fa-solid fa-comments"></i>
                    <span>People Connect</span>
                </a>

                <a href="{{ url('/hub/contacts') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/contacts') || request()->is('hub/contacts/*') ? 'active' : '' }}">
                    <i class="fa-solid fa-address-book"></i>
                    <span>ContactsHub</span>
                </a>

                <a href="{{ url('/hub/waha') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/waha') ? 'active' : '' }}">
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>Waha Manager</span>
                </a>

                <div class="nav-section-label mt-2">AI & Intelligence</div>

                <a href="{{ url('/hub/hedra-soul') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/hedra-soul') ? 'active' : '' }}">
                    <i class="fa-solid fa-brain"></i>
                    <span>Hedra Soul</span>
                </a>

                <a href="{{ url('/hub/proactive-ai') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/proactive-ai') ? 'active' : '' }}">
                    <i class="fa-solid fa-bolt"></i>
                    <span>Proactive AI</span>
                </a>

                <a href="{{ url('/hub/agents') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/agents') ? 'active' : '' }}">
                    <i class="fa-solid fa-robot"></i>
                    <span>AgentsHub</span>
                </a>

                <a href="{{ url('/hub/memory') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/memory') ? 'active' : '' }}">
                    <i class="fa-solid fa-database"></i>
                    <span>MemoryHub</span>
                </a>

                <div class="nav-section-label mt-2">Automation</div>

                <a href="{{ url('/hub/workflows') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/workflows') ? 'active' : '' }}">
                    <i class="fa-solid fa-diagram-project"></i>
                    <span>WorkflowsHub</span>
                </a>

                <a href="{{ url('/hub/tasks') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/tasks') ? 'active' : '' }}">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Task Objectives</span>
                </a>

                <a href="{{ url('/hub/scheduler') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/scheduler') ? 'active' : '' }}">
                    <i class="fa-regular fa-clock"></i>
                    <span>Scheduler</span>
                </a>

                <div class="nav-section-label mt-2">System</div>

                <a href="{{ url('/hub/logs') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/logs') ? 'active' : '' }}">
                    <i class="fa-solid fa-terminal"></i>
                    <span>LogsHub</span>
                </a>

                <a href="{{ url('/hub/models') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/models') ? 'active' : '' }}">
                    <i class="fa-solid fa-microchip"></i>
                    <span>AIModelsHub</span>
                </a>

                <a href="{{ url('/hub/apis') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/apis') ? 'active' : '' }}">
                    <i class="fa-solid fa-plug"></i>
                    <span>APIs & MCP</span>
                </a>

                <a href="{{ url('/hub/admin') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/admin') ? 'active' : '' }}">
                    <i class="fa-solid fa-server"></i>
                    <span>Admin</span>
                </a>

                <a href="{{ url('/hub/settings') }}"
                   class="list-group-item list-group-item-action {{ request()->is('hub/settings') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i>
                    <span>Settings</span>
                </a>

            </div>

            <!-- Sidebar Footer -->
            <div class="sidebar-footer">
                <span class="agent-status-orb online"></span>
                <span>Souly Online</span>
                <span class="ms-auto text-muted" style="font-family: 'JetBrains Mono'; font-size: 0.6rem;">v2.1.0</span>
            </div>
        </div>
        <!-- /#sidebar-wrapper -->

        <!-- ═══════════════════════════════════════════════════
             PAGE CONTENT
        ═══════════════════════════════════════════════════ -->
        <div id="page-content-wrapper" class="w-100 d-flex flex-column">

            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-dark px-4" id="main-topbar">
                <button class="btn btn-sm me-3" id="menu-toggle"
                        style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); color: var(--text-secondary); border-radius: 8px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-bars" style="font-size: 0.85rem;"></i>
                </button>

                <!-- Breadcrumb / Page Title -->
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 0.75rem; font-family: 'JetBrains Mono';">nexus /</span>
                    <span class="text-light fw-semibold" style="font-size: 0.85rem;">@yield('page_title', 'Dashboard')</span>
                </div>

                <div class="ms-auto d-flex align-items-center gap-3">
                    <!-- Queue Status Pill -->
                    <div id="queue-status-pill" class="d-none d-md-flex align-items-center gap-2 px-3 py-1 rounded-pill"
                         style="background: var(--nexus-teal-dim); border: 1px solid hsla(174,90%,41%,0.3); font-size: 0.72rem; font-family: 'JetBrains Mono';">
                        <span class="agent-status-orb busy" style="width: 6px; height: 6px;"></span>
                        <span id="queue-status-text" class="text-light">Queue Active</span>
                    </div>

                    <!-- Notifications -->
                    <a href="{{ route('hub.hedra-soul') }}" class="btn btn-sm position-relative" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; color: var(--text-secondary); text-decoration: none;">
                        <i class="fa-regular fa-bell" style="font-size: 0.85rem;"></i>
                        <span id="notif-badge" class="notif-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size: 0.55rem; @if(($unreadNotificationsCount ?? 0) === 0) display: none; @endif">{{ $unreadNotificationsCount ?? 0 }}</span>
                    </a>

                    <!-- Horizon Link -->
                    <a href="/horizon" target="_blank" class="btn btn-sm d-none d-md-flex align-items-center gap-2"
                       style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; color: var(--text-secondary); font-size: 0.78rem; padding: 6px 12px; text-decoration: none;">
                        <i class="fa-solid fa-gauge" style="font-size: 0.75rem;"></i>
                        <span>Horizon</span>
                    </a>

                    <!-- User -->
                    <div class="dropdown">
                        <button class="btn btn-sm dropdown-toggle d-flex align-items-center gap-2"
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: var(--text-primary); font-size: 0.82rem; padding: 6px 12px;"
                                data-bs-toggle="dropdown">
                            <div style="width: 22px; height: 22px; background: var(--nexus-blue-dim); border: 1px solid var(--nexus-blue-glow); border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-user" style="font-size: 0.6rem; color: var(--nexus-blue);"></i>
                            </div>
                            Admin
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="background: rgba(9,15,25,0.97); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 8px;">
                            <li><a class="dropdown-item rounded" href="{{ route('hub.settings') }}" style="color: var(--text-secondary); font-size: 0.83rem; padding: 8px 12px;"><i class="fa-regular fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item rounded" href="{{ route('hub.settings') }}" style="color: var(--text-secondary); font-size: 0.83rem; padding: 8px 12px;"><i class="fa-solid fa-gear me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider" style="border-color: rgba(255,255,255,0.06);"></li>
                            <li>
                                <a class="dropdown-item rounded text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="font-size: 0.83rem; padding: 8px 12px;">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                        <form id="logout-form" action="{{ route('hub.logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </nav>

            <!-- Main Content Area -->
            <div class="main-content" id="main-content">
                @yield('content')
            </div>
        </div>
        <!-- /#page-content-wrapper -->
    </div>
    <!-- /#wrapper -->

    <!-- ═══════════════════════════════════════════════════
         STATUS BAR (Fixed Bottom)
    ═══════════════════════════════════════════════════ -->
    <div id="nexus-statusbar">
        <!-- Left Section: Connections -->
        <div class="statusbar-section">
            <div class="statusbar-item">
                <span class="agent-status-orb online" style="width: 6px; height: 6px;"></span>
                <span id="sb-agent-status">Souly</span>
            </div>
            <div class="statusbar-item">
                <i class="fa-brands fa-whatsapp" style="color: hsl(142,76%,55%); font-size: 0.8rem;"></i>
                <span id="sb-waha-status">WAHA</span>
            </div>
            <div class="statusbar-item d-none d-md-flex">
                <i class="fa-solid fa-diagram-project" style="color: var(--nexus-blue); font-size: 0.75rem;"></i>
                <span id="sb-queue-count">0 Jobs</span>
            </div>
        </div>

        <!-- Center: Dynamic Task Status -->
        <div class="statusbar-center" id="statusbar-task-status">
            <i class="fa-solid fa-circle-check me-1" style="color: var(--nexus-teal); font-size: 0.65rem;"></i>
            Idle — System Ready
        </div>

        <!-- Right Section: System Metrics -->
        <div class="statusbar-section">
            <div class="statusbar-item d-none d-md-flex">
                <i class="fa-solid fa-memory" style="color: var(--nexus-blue); font-size: 0.75rem;"></i>
                <span id="sb-memory">— MB</span>
            </div>
            <div class="statusbar-item d-none d-md-flex">
                <i class="fa-solid fa-microchip" style="color: var(--nexus-teal); font-size: 0.75rem;"></i>
                <span id="sb-cpu">— %</span>
            </div>
            <div class="statusbar-item">
                <i class="fa-regular fa-clock" style="font-size: 0.75rem;"></i>
                <span id="sb-time">{{ now()->format('H:i') }}</span>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         SCRIPTS
    ═══════════════════════════════════════════════════ -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- NProgress -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Pusher & Laravel Echo -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>

    <script>
        // ── Laravel Echo / Reverb ──
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: '{{ config("broadcasting.connections.reverb.key") }}',
            wsHost: '{{ config("broadcasting.connections.reverb.host", "127.0.0.1") }}',
            wsPort: parseInt('{{ config("broadcasting.connections.reverb.port", "6001") }}'),
            wssPort: parseInt('{{ config("broadcasting.connections.reverb.port", "6001") }}'),
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
        });

        // ── NProgress for page/AJAX ──
        NProgress.configure({ showSpinner: false, minimum: 0.1 });
        $(document).ajaxStart(() => NProgress.start());
        $(document).ajaxStop(() => NProgress.done());
        window.addEventListener('load', () => NProgress.done());
        document.addEventListener('DOMContentLoaded', () => NProgress.start());

        // ── Sidebar Toggle ──
        $('#menu-toggle').on('click', function() {
            $('body').toggleClass('toggled');
        });

        // ── Active Sidebar Link Highlight ──
        (function() {
            const currentPath = window.location.pathname;
            $('#sidebar-wrapper .list-group-item').each(function() {
                const href = $(this).attr('href');
                if (href && currentPath.startsWith(href) && href !== '/') {
                    $(this).addClass('active');
                }
            });
        })();

        // ── Statusbar Clock ──
        function updateClock() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            $('#sb-time').text(`${h}:${m}`);
        }
        setInterval(updateClock, 10000);

        // ── NProgress Global Binding ──
        if (typeof NProgress !== 'undefined') {
            NProgress.configure({ showSpinner: false, speed: 400, minimum: 0.1 });
            $(document).ajaxStart(function() { NProgress.start(); });
            $(document).ajaxStop(function() { NProgress.done(); });
            $(window).on('beforeunload', function() { NProgress.start(); });
        }

        // ── Global Telemetry Polling ──
        function fetchTelemetry() {
            $.ajax({
                url: '{{ route('hub.system.telemetry') }}',
                method: 'GET',
                // prevent triggering NProgress for background telemetry
                global: false, 
                success: function(res) {
                    if (res && res.success) {
                        $('#sb-memory').text(res.data.memory_mb + ' MB');
                        $('#sb-cpu').text(res.data.cpu_percent + ' %');
                        $('#sb-queue-count').text(res.data.queue_count + ' Jobs');
                        
                        const wahaIcon = $('#sb-waha-status').prev('i');
                        if (res.data.waha_status === 'Online') {
                            wahaIcon.css('color', 'hsl(142,76%,55%)');
                            $('#sb-waha-status').text('WAHA (Online)');
                        } else {
                            wahaIcon.css('color', 'var(--error)');
                            $('#sb-waha-status').text('WAHA (Offline)');
                        }

                        const agentOrb = $('#sb-agent-status').prev('.agent-status-orb');
                        if (res.data.agent_status === 'Busy') {
                            agentOrb.removeClass('online offline').addClass('busy');
                            $('#sb-agent-status').text('System Busy');
                        } else {
                            agentOrb.removeClass('busy offline').addClass('online');
                            $('#sb-agent-status').text('System Online');
                        }
                    }
                }
            });
        }
        setInterval(fetchTelemetry, 10000);
        setTimeout(fetchTelemetry, 1000);

        // ── Global Nexus Object ──
        window.Nexus = {
            showTaskLoader: function(message, sub) {
                $('#nexus-loader-text').text(message || 'Processing...');
                $('#nexus-loader-sub').text(sub || 'Please wait...');
                $('#nexus-loader-progress').css('width', '0%');
                $('#nexus-global-loader').css('display', 'flex');
            },
            updateTaskLoader: function(percent, sub) {
                $('#nexus-loader-progress').css('width', percent + '%');
                if (sub) $('#nexus-loader-sub').text(sub);
            },
            hideTaskLoader: function() {
                $('#nexus-global-loader').css('display', 'none');
            },
            updateStatusBar: function(status, type) {
                const icons = {
                    running:  '<i class="fa-solid fa-spinner fa-spin me-1" style="color: var(--nexus-teal);"></i>',
                    success:  '<i class="fa-solid fa-circle-check me-1" style="color: hsl(142,76%,55%);"></i>',
                    error:    '<i class="fa-solid fa-circle-xmark me-1" style="color: var(--error);"></i>',
                    warning:  '<i class="fa-solid fa-triangle-exclamation me-1" style="color: var(--amber);"></i>',
                    idle:     '<i class="fa-solid fa-circle-check me-1" style="color: var(--nexus-teal); font-size: 0.65rem;"></i>',
                };
                const icon = icons[type] || icons.running;
                $('#statusbar-task-status').html(icon + status);
                if (type === 'running') {
                    $('#statusbar-task-status').addClass('active');
                } else {
                    $('#statusbar-task-status').removeClass('active');
                }
            },
            clearStatusBar: function() {
                this.updateStatusBar('Idle — System Ready', 'idle');
            },
            setAutopilot: function(enabled) {
                if (enabled) {
                    $('#autopilot-banner').addClass('visible');
                } else {
                    $('#autopilot-banner').removeClass('visible');
                }
            },
            notify: function(message, type) {
                // Simple toast notification
                const colors = {
                    success: 'var(--success-bright)',
                    error:   'var(--error)',
                    warning: 'var(--amber)',
                    info:    'var(--nexus-blue)',
                };
                const color = colors[type] || colors.info;
                const toast = $(`
                    <div style="position:fixed;top:80px;right:20px;z-index:9000;
                         background:rgba(9,15,25,0.97);border:1px solid ${color}30;
                         border-left:3px solid ${color};border-radius:10px;
                         padding:12px 16px;max-width:300px;font-size:0.82rem;
                         color:var(--text-primary);backdrop-filter:blur(12px);
                         animation:fadeInSlideUp 0.3s var(--ease-spring) forwards;
                         box-shadow:0 8px 24px rgba(0,0,0,0.4);">
                        ${message}
                    </div>
                `);
                $('body').append(toast);
                setTimeout(() => toast.fadeOut(300, function() { $(this).remove(); }), 4000);
            }
        };

        // ── Real-time: Listen for Job Progress events ──
        window.Echo.channel('nexus-system')
            .listen('JobProgressUpdated', (e) => {
                if (e.status === 'running') {
                    Nexus.updateStatusBar(`${e.job_name || 'Job'}: ${e.message || 'Running...'}`, 'running');
                    $('#queue-status-pill').removeClass('d-none');
                    $('#queue-status-text').text(e.job_name || 'Queue Active');
                } else if (e.status === 'completed') {
                    Nexus.updateStatusBar(`${e.job_name || 'Job'} completed`, 'success');
                    setTimeout(() => { Nexus.clearStatusBar(); $('#queue-status-pill').addClass('d-none'); }, 3000);
                } else if (e.status === 'failed') {
                    Nexus.updateStatusBar(`${e.job_name || 'Job'} failed`, 'error');
                    setTimeout(() => { Nexus.clearStatusBar(); $('#queue-status-pill').addClass('d-none'); }, 5000);
                }
            });

    </script>

    @stack('scripts')

    <!-- Custom JS -->
    @if(file_exists(public_path('js/app.js')))
    <script src="{{ asset('js/app.js') }}"></script>
    @endif

</body>
</html>
