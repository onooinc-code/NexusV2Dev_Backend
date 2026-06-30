@extends('layouts.app')

@push('styles')
<style>
    .job-card {
        background: rgba(22, 27, 34, 0.5);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--nexus-border);
        border-radius: 12px;
        padding: 20px;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    }
    .job-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        border-color: rgba(99, 102, 241, 0.3);
    }
    .job-card .action-bar {
        opacity: 0;
        transition: opacity 0.2s;
    }
    .job-card:hover .action-bar {
        opacity: 1;
    }
    
    .pulse-bar {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, var(--nexus-primary), var(--nexus-secondary));
        animation: pulseBar 2s infinite;
    }
    @keyframes pulseBar {
        0% { opacity: 0.5; }
        50% { opacity: 1; }
        100% { opacity: 0.5; }
    }

    .cron-badge {
        background: rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        padding: 6px 10px;
        font-family: monospace;
        font-size: 0.8rem;
        color: #d1d5db;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .modal-content {
        background: var(--nexus-panel);
        border: 1px solid var(--nexus-border);
        border-radius: 16px;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 text-light mb-1"><i class="fa-solid fa-clock text-primary me-2 animate-pulse"></i> Task Scheduler</h2>
        <p class="text-muted small mb-0">Manage recurring tasks, webhook triggers, and cron expressions.</p>
    </div>
    <div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#jobModal">
            <i class="fa-solid fa-plus me-1"></i> New Job
        </button>
    </div>
</div>

<div class="row animate-fade-in stagger-1">
    <!-- Active Job -->
    <div class="col-md-4 mb-4">
        <div class="job-card">
            <div class="pulse-bar"></div>
            
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="text-light mb-1" style="font-size: 1.1rem;">Sync Waha Data</h5>
                    <span class="text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px; font-family: monospace;">COMMAND</span>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">ACTIVE</span>
            </div>
            
            <div class="d-flex flex-column gap-3">
                <div class="cron-badge">
                    <i class="fa-regular fa-calendar text-primary"></i> 0 * * * *
                </div>
                <div class="d-flex flex-column text-muted font-monospace" style="font-size: 0.65rem; line-height: 1.6;">
                    <span>NEXT: 2026-06-21 12:00:00</span>
                    <span>LAST: 2026-06-21 11:00:00</span>
                </div>
            </div>

            <div class="action-bar d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-secondary">
                <button class="btn btn-sm btn-link text-muted hover-text-white p-1" title="Pause"><i class="fa-solid fa-pause"></i></button>
                <button class="btn btn-sm btn-link text-muted hover-text-white p-1" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                <button class="btn btn-sm btn-link text-danger hover-text-white p-1" title="Delete"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>
    </div>

    <!-- Paused Job -->
    <div class="col-md-4 mb-4">
        <div class="job-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="text-light mb-1" style="font-size: 1.1rem;">Cleanup Database Logs</h5>
                    <span class="text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px; font-family: monospace;">JOB</span>
                </div>
                <span class="badge bg-warning bg-opacity-10 text-warning text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">PAUSED</span>
            </div>
            
            <div class="d-flex flex-column gap-3">
                <div class="cron-badge">
                    <i class="fa-regular fa-calendar text-primary"></i> 0 0 * * 0
                </div>
                <div class="d-flex flex-column text-muted font-monospace" style="font-size: 0.65rem; line-height: 1.6;">
                    <span>NEXT: Pending calculation</span>
                    <span>LAST: 2026-06-14 00:00:00</span>
                </div>
            </div>

            <div class="action-bar d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-secondary">
                <button class="btn btn-sm btn-link text-muted hover-text-white p-1" title="Resume"><i class="fa-solid fa-play"></i></button>
                <button class="btn btn-sm btn-link text-muted hover-text-white p-1" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                <button class="btn btn-sm btn-link text-danger hover-text-white p-1" title="Delete"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>
    </div>

    <!-- Webhook Job -->
    <div class="col-md-4 mb-4">
        <div class="job-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="text-light mb-1" style="font-size: 1.1rem;">Ping Healthcheck</h5>
                    <span class="text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px; font-family: monospace;">WEBHOOK</span>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">ACTIVE</span>
            </div>
            
            <div class="d-flex flex-column gap-3">
                <div class="cron-badge">
                    <i class="fa-regular fa-calendar text-primary"></i> */5 * * * *
                </div>
                <div class="d-flex flex-column text-muted font-monospace" style="font-size: 0.65rem; line-height: 1.6;">
                    <span>NEXT: 2026-06-21 11:25:00</span>
                    <span>LAST: 2026-06-21 11:20:00</span>
                </div>
            </div>

            <div class="action-bar d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-secondary">
                <button class="btn btn-sm btn-link text-muted hover-text-white p-1" title="Pause"><i class="fa-solid fa-pause"></i></button>
                <button class="btn btn-sm btn-link text-muted hover-text-white p-1" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                <button class="btn btn-sm btn-link text-danger hover-text-white p-1" title="Delete"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>
    </div>

    <!-- Upcoming Jobs -->
    <div class="col-md-8 mb-4">
        <h5 class="text-light mb-3">Upcoming Executions</h5>
        <div class="scheduler-timeline">
            @forelse($schedules as $schedule)
            <div class="timeline-item">
                <div class="timeline-time">{{ $schedule->next_run_at ? \Carbon\Carbon::parse($schedule->next_run_at)->format('H:i') : 'N/A' }}</div>
                <div class="timeline-content">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-light">{{ $schedule->workflow->name ?? 'Unknown Workflow' }}</strong>
                        <span class="badge {{ $schedule->is_active ? 'bg-primary' : 'bg-secondary' }}">{{ $schedule->is_active ? 'Scheduled' : 'Paused' }}</span>
                    </div>
                    <p class="text-muted small mb-0">{{ $schedule->workflow->description ?? 'No description' }}</p>
                    <div class="mt-2 text-muted" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-clock me-1"></i> Cron: <code>{{ $schedule->cron_expression }}</code>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-muted text-center p-4 border border-secondary border-dashed rounded">No scheduled workflows found.</div>
            @endforelse
        </div>
    </div>
</div>

<!-- Job Modal -->
<div class="modal fade" id="jobModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light">Create Job</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <input type="text" class="form-control" placeholder="Job Name">
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <select class="form-select">
                            <option value="command">Command</option>
                            <option value="job">Queue Job</option>
                            <option value="webhook">Webhook</option>
                            <option value="script">Script</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <input type="text" class="form-control font-monospace" placeholder="* * * * *">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted font-monospace" style="font-size: 0.75rem;">Payload (JSON)</label>
                    <textarea class="form-control font-monospace" rows="5" placeholder="{}" style="font-size: 0.8rem;"></textarea>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveJob()" data-bs-dismiss="modal">Save Job</button>
            </div>
        </div>
    </div>
</div>

@endsection

@stack('scripts')
<script>
    function saveJob() {
        Nexus.showTaskLoader('Saving Scheduler Configuration...');
        setTimeout(() => {
            Nexus.hideTaskLoader();
            // UI refresh logic would go here
        }, 1000);
    }
</script>
