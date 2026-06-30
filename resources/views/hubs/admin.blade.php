@extends('layouts.app')

@push('styles')
<style>
    .system-card {
        background: rgba(22, 27, 34, 0.5);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--nexus-border);
        border-radius: 12px;
        padding: 20px;
        transition: transform 0.2s;
    }
    .system-card:hover {
        transform: translateY(-2px);
    }
    .nav-pills-custom {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50px;
        padding: 5px;
        display: inline-flex;
    }
    .nav-pills-custom .nav-link {
        color: var(--nexus-text-muted);
        border-radius: 50px;
        padding: 8px 20px;
        font-weight: 500;
        font-size: 0.9rem;
    }
    .nav-pills-custom .nav-link.active {
        background-color: rgba(22, 27, 34, 0.8);
        color: #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .terminal-box {
        background: #000;
        border: 1px solid var(--nexus-border);
        border-radius: 8px;
        height: 300px;
        overflow-y: auto;
        padding: 15px;
        font-family: monospace;
        font-size: 0.8rem;
        color: #d1d5db;
    }
    .terminal-box .error { color: #f87171; }
    .terminal-box .warn { color: #fbbf24; }
    .terminal-box .info { color: #60a5fa; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 text-light mb-1"><i class="fa-solid fa-screwdriver-wrench text-secondary me-2"></i> System Control Panel</h2>
        <p class="text-muted small mb-0">Build control, dead-letter queues, raw logs, and core service management.</p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="form-check form-switch text-light">
            <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
            <label class="form-check-label small" for="autoRefresh">Auto-Refresh (5s)</label>
        </div>
        <button class="btn btn-danger btn-sm"><i class="fa-solid fa-power-off me-1"></i> Restart Core Services</button>
    </div>
</div>

<!-- Tabs -->
<div class="text-center mb-4 animate-fade-in stagger-1">
    <ul class="nav nav-pills nav-pills-custom" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pills-overview" type="button">Overview</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-services" type="button">Services</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-dlq" type="button">Dead Letter Queue</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-logs" type="button">Raw Logs</button>
        </li>
    </ul>
</div>

<div class="tab-content animate-fade-in stagger-2" id="pills-tabContent">
    
    <!-- Overview -->
    <div class="tab-pane fade show active" id="pills-overview">
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="system-card text-center">
                    <h6 class="text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">CPU Load</h6>
                    <h2 class="text-light mb-0">12%</h2>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="system-card text-center">
                    <h6 class="text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">Memory Usage</h6>
                    <h2 class="text-light mb-0">1.4 GB</h2>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="system-card text-center">
                    <h6 class="text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">Queue Backlog</h6>
                    <h2 class="text-success mb-0">0</h2>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="system-card text-center" style="border-color: rgba(239, 68, 68, 0.3);">
                    <h6 class="text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">DLQ Items</h6>
                    <h2 class="text-danger mb-0">3</h2>
                </div>
            </div>
        </div>

        <div class="card bg-transparent border-secondary mt-2">
            <div class="card-header border-secondary text-light">Build Control</div>
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="text-light">Recompile Frontend Assets</h6>
                        <p class="text-muted small mb-0">Runs `npm run build` to compile SCSS/JS assets. Required after layout changes.</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-outline-primary" onclick="runBuild()"><i class="fa-solid fa-hammer me-1"></i> Trigger Build</button>
                    </div>
                </div>
                <hr class="border-secondary my-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="text-light">Clear Application Cache</h6>
                        <p class="text-muted small mb-0">Runs `php artisan optimize:clear`. Flushes view, route, and config caches.</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-outline-warning" onclick="clearCache()"><i class="fa-solid fa-broom me-1"></i> Clear Cache</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Services -->
    <div class="tab-pane fade" id="pills-services">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th>Service Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Uptime</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="fa-solid fa-server text-primary me-2"></i> Horizon Queue Worker</td>
                        <td>Background</td>
                        <td><span class="badge bg-success bg-opacity-10 text-success">RUNNING</span></td>
                        <td>14d 2h 45m</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-rotate-right"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="fa-solid fa-bolt text-warning me-2"></i> Reverb WebSocket Server</td>
                        <td>Daemon</td>
                        <td><span class="badge bg-success bg-opacity-10 text-success">RUNNING</span></td>
                        <td>14d 2h 45m</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-rotate-right"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- DLQ -->
    <div class="tab-pane fade" id="pills-dlq">
        <div class="card bg-transparent border-danger border-opacity-50">
            <div class="card-header border-danger border-opacity-50 d-flex justify-content-between align-items-center">
                <span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i> Failed Jobs (3)</span>
                <button class="btn btn-sm btn-danger"><i class="fa-solid fa-rotate-right me-1"></i> Retry All</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-dark table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Job ID</th>
                            <th>Exception</th>
                            <th>Failed At</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-monospace text-muted small">#4921</td>
                            <td class="text-danger small">Stripe\Exception\ApiErrorException</td>
                            <td class="text-muted small">2 hours ago</td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-secondary">Retry</button></td>
                        </tr>
                        <tr>
                            <td class="font-monospace text-muted small">#4918</td>
                            <td class="text-danger small">GuzzleHttp\Exception\ConnectException</td>
                            <td class="text-muted small">4 hours ago</td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-secondary">Retry</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Raw Logs -->
    <div class="tab-pane fade" id="pills-logs">
        <div class="terminal-box" id="sysLogs">
            <div class="info">[2026-06-21 10:00:00] local.INFO: Application cache cleared.</div>
            <div class="warn">[2026-06-21 10:15:22] local.WARNING: Webhook signature missing in headers.</div>
            <div class="error">[2026-06-21 11:32:01] local.ERROR: Stripe webhook verification failed. {"exception":"[object] (Stripe\\Exception\\SignatureVerificationException(code: 0)..."}</div>
            <div>[2026-06-21 11:35:10] local.INFO: Agent 'QA Agent' executed task objective #14.</div>
        </div>
    </div>

</div>
@endsection

@stack('scripts')
<script>
    function runBuild() {
        Nexus.showTaskLoader('Compiling Assets with Vite...');
        setTimeout(() => {
            Nexus.hideTaskLoader();
            alert('Build successful!');
        }, 2000);
    }

    function clearCache() {
        Nexus.showTaskLoader('Clearing application cache...');
        setTimeout(() => {
            Nexus.hideTaskLoader();
            alert('Cache cleared!');
        }, 1000);
    }
</script>
