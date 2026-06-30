@extends('layouts.app')

@push('styles')
<style>
    .stat-card {
        background: rgba(22, 27, 34, 0.5);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--nexus-border);
        border-radius: 12px;
        padding: 20px;
        transition: transform 0.2s;
    }
    .stat-card:hover {
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

    .rule-card {
        background: rgba(22, 27, 34, 0.5);
        border: 1px solid var(--nexus-border);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        transition: all 0.2s;
    }
    .rule-card:hover {
        border-color: rgba(99, 102, 241, 0.4);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .rule-card.paused {
        opacity: 0.6;
    }
    .rule-card.paused:hover {
        opacity: 1;
    }

    .modal-content {
        background: var(--nexus-panel);
        border: 1px solid var(--nexus-border);
        border-radius: 16px;
    }
    .modal-header {
        border-bottom: 1px solid var(--nexus-border);
    }
    .modal-footer {
        border-top: 1px solid var(--nexus-border);
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 text-light mb-1"><i class="fa-solid fa-bolt text-warning me-2"></i> Proactive AI Engine</h2>
        <p class="text-muted small mb-0">Autonomous Event-Condition-Action (ECA) rules for Souly.</p>
    </div>
    <div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRuleModal">
            <i class="fa-solid fa-plus me-1"></i> New Rule
        </button>
    </div>
</div>

<!-- Stats Grid -->
<div class="row mb-4 animate-fade-in stagger-1">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="text-muted mb-0 text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">Total Rules</h6>
                <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex justify-content-center align-items-center" style="width: 35px; height: 35px;"><i class="fa-solid fa-code-branch"></i></div>
            </div>
            <h3 class="text-light mt-3 mb-0">12</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="text-muted mb-0 text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">Active Rules</h6>
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex justify-content-center align-items-center" style="width: 35px; height: 35px;"><i class="fa-solid fa-play"></i></div>
            </div>
            <h3 class="text-light mt-3 mb-0">8</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="text-muted mb-0 text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">Pending Triggers</h6>
                <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex justify-content-center align-items-center" style="width: 35px; height: 35px;"><i class="fa-solid fa-clock"></i></div>
            </div>
            <h3 class="text-light mt-3 mb-0">3</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="text-muted mb-0 text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">Actions Taken</h6>
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex justify-content-center align-items-center" style="width: 35px; height: 35px;"><i class="fa-solid fa-check-double"></i></div>
            </div>
            <h3 class="text-light mt-3 mb-0">1,204</h3>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="text-center mb-4 animate-fade-in stagger-2">
    <ul class="nav nav-pills nav-pills-custom" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-rules-tab" data-bs-toggle="pill" data-bs-target="#pills-rules" type="button" role="tab">ECA Rules</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-triggers-tab" data-bs-toggle="pill" data-bs-target="#pills-triggers" type="button" role="tab">Scheduled Triggers</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-logs-tab" data-bs-toggle="pill" data-bs-target="#pills-logs" type="button" role="tab">Action Logs</button>
        </li>
    </ul>
</div>

<!-- Tab Content -->
<div class="tab-content animate-fade-in stagger-3" id="pills-tabContent">
    <!-- Rules Tab -->
    <div class="tab-pane fade show active" id="pills-rules" role="tabpanel">
        
        <div class="rule-card" id="rule-1">
            <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-start">
                    <i class="fa-solid fa-circle text-secondary mt-1 me-3 animate-pulse" style="font-size: 0.7rem;"></i>
                    <div>
                        <h5 class="text-light mb-1" style="font-size: 1.1rem; font-weight: 500;">When I receive an email from "vip@client.com", draft a polite response and send me a WhatsApp notification.</h5>
                        <div class="mt-2">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary me-2"><i class="fa-solid fa-envelope me-1"></i> Email Event</span>
                            <span class="text-muted small">Created 2 days ago</span>
                        </div>
                    </div>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary border-0 text-muted" onclick="toggleRule('rule-1')"><i class="fa-solid fa-pause"></i></button>
                    <button class="btn btn-sm btn-outline-danger border-0 text-muted hover-text-danger"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        </div>

        <div class="rule-card" id="rule-2">
            <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-start">
                    <i class="fa-solid fa-circle text-secondary mt-1 me-3 animate-pulse" style="font-size: 0.7rem;"></i>
                    <div>
                        <h5 class="text-light mb-1" style="font-size: 1.1rem; font-weight: 500;">Every morning at 8:00 AM, summarize my unread GitHub notifications and add a task if I am mentioned.</h5>
                        <div class="mt-2">
                            <span class="badge bg-warning bg-opacity-10 text-warning me-2"><i class="fa-regular fa-clock me-1"></i> Time Event</span>
                            <span class="text-muted small">Created last week</span>
                        </div>
                    </div>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary border-0 text-muted" onclick="toggleRule('rule-2')"><i class="fa-solid fa-pause"></i></button>
                    <button class="btn btn-sm btn-outline-danger border-0 text-muted hover-text-danger"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        </div>

        <div class="rule-card paused" id="rule-3">
            <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-start">
                    <i class="fa-solid fa-circle text-secondary mt-1 me-3" style="font-size: 0.7rem; opacity: 0.3;"></i>
                    <div>
                        <h5 class="text-light mb-1" style="font-size: 1.1rem; font-weight: 500;">If server CPU goes above 90%, restart the queue worker immediately.</h5>
                        <div class="mt-2">
                            <span class="badge bg-danger bg-opacity-10 text-danger me-2"><i class="fa-solid fa-server me-1"></i> System Event</span>
                            <span class="text-muted small">Created 1 month ago</span>
                        </div>
                    </div>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary border-0 text-muted" onclick="toggleRule('rule-3')"><i class="fa-solid fa-play"></i></button>
                    <button class="btn btn-sm btn-outline-danger border-0 text-muted hover-text-danger"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        </div>

    </div>

    <!-- Triggers Tab -->
    <div class="tab-pane fade" id="pills-triggers" role="tabpanel">
        <div class="row pt-3">
            @forelse($triggers as $trigger)
            <div class="col-md-6 mb-3">
                <div class="trigger-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-bolt text-warning me-2"></i>
                            <strong class="text-light">{{ Str::title(str_replace('_', ' ', $trigger->trigger_type)) }}</strong>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" {{ $trigger->status == 'active' || $trigger->status == 'pending' ? 'checked' : '' }}>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">Context: {{ is_array($trigger->context_payload) ? json_encode($trigger->context_payload) : $trigger->context_payload }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-secondary bg-opacity-25 text-light">Status: {{ $trigger->status }}</span>
                        <span class="text-muted" style="font-size: 0.75rem;">Next: {{ $trigger->next_run_at ? \Carbon\Carbon::parse($trigger->next_run_at)->diffForHumans() : 'N/A' }}</span>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <i class="fa-regular fa-calendar-check text-muted mb-3" style="font-size: 3rem; opacity: 0.5;"></i>
                <h5 class="text-light">No Pending Triggers</h5>
                <p class="text-muted">All scheduled actions have been executed.</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Logs Tab -->
    <div class="tab-pane fade" id="pills-logs" role="tabpanel">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Subject</th>
                        <th>Action Taken</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->diffForHumans() }}</td>
                        <td>{{ $log->subject ?? 'Notification Sent' }}</td>
                        <td><em>To: {{ $log->recipient }} (Channel: {{ $log->channel }})</em></td>
                        <td><span class="badge {{ $log->status == 'sent' ? 'bg-success' : 'bg-warning' }} bg-opacity-10 text-{{ $log->status == 'sent' ? 'success' : 'warning' }}">{{ $log->status }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No actions logged yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Rule Modal -->
<div class="modal fade" id="newRuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-light"><i class="fa-solid fa-wand-magic-sparkles text-secondary me-2"></i> Create Autonomous Rule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <label class="form-label text-light">Natural Language Rule</label>
                <textarea class="form-control mb-3" rows="3" placeholder="e.g., If I get an email with 'invoice' attached, save the attachment to Google Drive and notify me on WhatsApp."></textarea>
                
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="connectMemory" checked>
                    <label class="form-check-label text-light" for="connectMemory">Connect Global Memory (Allows Souly to cross-reference past interactions)</label>
                </div>

                <p class="text-muted small mb-2">Quick Examples:</p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-white bg-opacity-10 p-2 cursor-pointer border border-secondary border-opacity-25" style="cursor: pointer;">Remind me to follow up...</span>
                    <span class="badge bg-white bg-opacity-10 p-2 cursor-pointer border border-secondary border-opacity-25" style="cursor: pointer;">If server goes down...</span>
                    <span class="badge bg-white bg-opacity-10 p-2 cursor-pointer border border-secondary border-opacity-25" style="cursor: pointer;">When a new contact is added...</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="createRule()">Deploy Rule</button>
            </div>
        </div>
    </div>
</div>

@endsection

@stack('scripts')
<script>
    function toggleRule(id) {
        const el = document.getElementById(id);
        const btn = el.querySelector('.fa-pause, .fa-play');
        const dot = el.querySelector('.fa-circle');
        
        if (el.classList.contains('paused')) {
            el.classList.remove('paused');
            btn.classList.remove('fa-play');
            btn.classList.add('fa-pause');
            dot.classList.add('animate-pulse');
            dot.style.opacity = '1';
        } else {
            el.classList.add('paused');
            btn.classList.remove('fa-pause');
            btn.classList.add('fa-play');
            dot.classList.remove('animate-pulse');
            dot.style.opacity = '0.3';
        }
    }

    function createRule() {
        Nexus.showTaskLoader('Compiling natural language to ECA Graph...');
        setTimeout(() => {
            Nexus.hideTaskLoader();
            alert('Rule Deployed Successfully!');
        }, 1500);
    }
</script>
