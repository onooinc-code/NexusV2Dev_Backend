@extends('layouts.app')
@section('page_title', 'AgentsHub')

@push('styles')
<style>
.agent-card {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    padding: 22px;
    transition: all 0.25s var(--ease-smooth);
    position: relative;
    overflow: hidden;
    height: 100%;
}
.agent-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--nexus-blue-glow), transparent);
}
.agent-card:hover {
    border-color: var(--nexus-blue-glow);
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.5), 0 0 0 1px var(--nexus-blue-glow);
}

/* Status Orb with glow ring */
.agent-orb-lg {
    width: 12px; height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
    position: relative;
}
.agent-orb-lg::after {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: 50%;
    animation: pulse-ring 2s infinite;
}
.agent-orb-lg.online  { background: var(--success-bright); }
.agent-orb-lg.online::after { border: 1px solid var(--success-bright); }
.agent-orb-lg.busy    { background: var(--amber); }
.agent-orb-lg.busy::after   { border: 1px solid var(--amber); }
.agent-orb-lg.offline { background: var(--text-muted); }
.agent-orb-lg.offline::after { display: none; }

@keyframes pulse-ring {
    0%   { transform: scale(1); opacity: 0.8; }
    50%  { transform: scale(1.5); opacity: 0; }
    100% { transform: scale(1); opacity: 0; }
}

/* Agent Avatar */
.agent-avatar {
    width: 52px; height: 52px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    border: 1px solid var(--glass-border);
    flex-shrink: 0;
}

/* Token bar */
.token-bar {
    height: 3px;
    background: rgba(255,255,255,0.06);
    border-radius: 2px;
    overflow: hidden;
    margin-top: 6px;
}
.token-bar-fill {
    height: 100%;
    border-radius: 2px;
    background: linear-gradient(90deg, var(--nexus-blue), var(--nexus-teal));
    transition: width 0.5s var(--ease-spring);
}

/* Execution Log Terminal */
.exec-terminal {
    background: hsl(224, 71%, 2%);
    border-radius: 8px;
    padding: 14px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.72rem;
    line-height: 1.8;
    height: 240px;
    overflow-y: auto;
    border: 1px solid var(--glass-border);
}
.exec-terminal::-webkit-scrollbar { width: 3px; }
.exec-terminal::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.06); }
.exec-line { display: block; }
.exec-line-info    { color: var(--nexus-teal); }
.exec-line-success { color: hsl(142,76%,55%); }
.exec-line-error   { color: var(--error); }
.exec-line-warn    { color: var(--amber); }
</style>
@endpush

@section('content')
<div class="d-flex flex-column gap-4 animate-in">

    {{-- ═══ HEADER ═══ --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 stagger-1" style="opacity: 0;">
        <div class="d-flex align-items-center gap-3">
            <div style="width: 42px; height: 42px; background: hsla(258,90%,66%,0.12); border: 1px solid hsla(258,90%,66%,0.3); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-robot" style="color: var(--nexus-purple); font-size: 1.1rem;"></i>
            </div>
            <div>
                <h1 class="mb-0" style="font-size: 1.4rem; font-weight: 700; letter-spacing: -0.02em;">AgentsHub</h1>
                <p class="text-muted mb-0" style="font-size: 0.8rem;">Configure & monitor autonomous AI agents</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-secondary); border-radius: 8px; font-size: 0.78rem; padding: 6px 14px;">
                <i class="fa-solid fa-rotate-right me-1"></i> Refresh Status
            </button>
            <button class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" data-bs-target="#createAgentDrawer" style="font-size: 0.78rem; padding: 6px 16px; border-radius: 8px;">
                <i class="fa-solid fa-plus me-1"></i> New Agent
            </button>
        </div>
    </div>

    {{-- ═══ AGENTS GRID ═══ --}}
    <div class="row g-4 stagger-2" style="opacity: 0;">
        @forelse($agents as $agent)
        @php
            $statusKey = strtolower($agent->status ?? 'offline');
            if (in_array($statusKey, ['active', 'online'])) $statusKey = 'online';
            elseif (in_array($statusKey, ['busy', 'running', 'processing'])) $statusKey = 'busy';
            else $statusKey = 'offline';

            $avatarColors = [
                'online'  => ['bg' => 'hsla(142,72%,29%,0.15)', 'icon' => 'hsl(142,76%,55%)'],
                'busy'    => ['bg' => 'var(--amber-dim)', 'icon' => 'var(--amber)'],
                'offline' => ['bg' => 'rgba(255,255,255,0.04)', 'icon' => 'var(--text-muted)'],
            ];
            $colors = $avatarColors[$statusKey];
        @endphp
        <div class="col-12 col-md-6 col-xl-4">
            <div class="agent-card">

                {{-- Top Row --}}
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="agent-avatar" style="background: {{ $colors['bg'] }}; border-color: {{ $colors['bg'] }};">
                        <i class="fa-solid fa-robot" style="color: {{ $colors['icon'] }};"></i>
                    </div>
                    <div class="flex-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="agent-orb-lg {{ $statusKey }}"></span>
                            <h6 class="mb-0 fw-semibold" style="font-size: 0.95rem;">{{ $agent->name }}</h6>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--nexus-blue);">{{ $agent->role ?? 'AI Agent' }}</div>
                    </div>
                    <span class="nx-tag">{{ strtoupper($agent->status ?? 'offline') }}</span>
                </div>

                {{-- Model badge --}}
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span style="font-size: 0.65rem; font-family: 'JetBrains Mono'; padding: 3px 8px; border-radius: 5px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); color: var(--text-muted);">
                        <i class="fa-solid fa-microchip me-1"></i>{{ $agent->model ?? 'gemini-pro' }}
                    </span>
                    @if($agent->temperature)
                    <span style="font-size: 0.65rem; font-family: 'JetBrains Mono'; padding: 3px 8px; border-radius: 5px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); color: var(--text-muted);">
                        🌡 {{ $agent->temperature }}
                    </span>
                    @endif
                    @if($agent->max_tokens)
                    <span style="font-size: 0.65rem; font-family: 'JetBrains Mono'; padding: 3px 8px; border-radius: 5px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); color: var(--text-muted);">
                        📊 {{ number_format($agent->max_tokens) }} tkns
                    </span>
                    @endif
                </div>

                {{-- System prompt preview --}}
                @if($agent->system_prompt)
                <div class="mb-3 p-2 rounded" style="background: rgba(0,0,0,0.3); border: 1px solid var(--glass-border); font-size: 0.72rem; color: var(--text-muted); font-family: 'JetBrains Mono'; max-height: 50px; overflow: hidden; line-height: 1.5; text-overflow: ellipsis;">
                    {{ Str::limit($agent->system_prompt, 120) }}
                </div>
                @endif

                {{-- Token usage bar --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size: 0.65rem; color: var(--text-muted); font-family: 'JetBrains Mono';">Token Budget</span>
                        <span style="font-size: 0.65rem; color: var(--nexus-teal); font-family: 'JetBrains Mono';">{{ number_format($agent->tokens_used ?? 0) }} / {{ number_format($agent->max_tokens ?? 8000) }}</span>
                    </div>
                    <div class="token-bar">
                        @php $tokenPct = $agent->max_tokens ? min(100, ($agent->tokens_used ?? 0) / $agent->max_tokens * 100) : 0; @endphp
                        <div class="token-bar-fill" style="width: {{ $tokenPct }}%;"></div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-2 mt-auto">
                    <button class="btn btn-sm flex-1"
                            style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); color: var(--text-secondary); font-size: 0.75rem; border-radius: 7px;"
                            data-agent-id="{{ $agent->id }}"
                            data-agent="{{ json_encode($agent) }}"
                            onclick="openAgentDrawer(this)">
                        <i class="fa-solid fa-sliders me-1"></i> Configure
                    </button>
                    <button class="btn btn-sm"
                            style="background: var(--nexus-blue-dim); border: 1px solid var(--nexus-blue-glow); color: var(--nexus-blue); font-size: 0.75rem; border-radius: 7px;"
                            onclick="executeAgent({{ $agent->id }}, '{{ $agent->name }}')">
                        <i class="fa-solid fa-play me-1"></i> Execute
                    </button>
                    <button class="btn btn-sm agent-toggle-btn"
                            style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); color: var(--text-muted); font-size: 0.75rem; border-radius: 7px; width: 32px;"
                            data-agent-id="{{ $agent->id }}"
                            data-current-status="{{ $agent->status }}"
                            title="{{ $statusKey === 'online' ? 'Deactivate' : 'Activate' }}">
                        <i class="fa-solid {{ $statusKey === 'online' ? 'fa-pause' : 'fa-power-off' }}"></i>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <div style="width: 64px; height: 64px; background: hsla(258,90%,66%,0.1); border: 1px solid hsla(258,90%,66%,0.25); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i class="fa-solid fa-robot" style="color: var(--nexus-purple); font-size: 1.5rem;"></i>
            </div>
            <h5 class="fw-semibold mb-2">No Agents Configured</h5>
            <p class="text-muted" style="font-size: 0.83rem;">Create your first AI agent to get started</p>
            <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="offcanvas" data-bs-target="#createAgentDrawer">
                <i class="fa-solid fa-plus me-1"></i> Create Agent
            </button>
        </div>
        @endforelse
    </div>

</div>

{{-- ═══ CONFIGURE AGENT OFFCANVAS ═══ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="configureAgentDrawer" style="width: 480px;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title d-flex align-items-center gap-2">
            <i class="fa-solid fa-sliders" style="color: var(--nexus-purple);"></i>
            <span id="drawer-agent-name">Configure Agent</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="configure-agent-form">
            <input type="hidden" id="ca-agent-id">
            <div class="mb-4">
                <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Model</label>
                <select id="ca-model" class="form-select" style="font-size: 0.83rem;">
                    <option value="gemini-2.0-flash">Gemini 2.0 Flash</option>
                    <option value="gemini-2.0-pro">Gemini 2.0 Pro</option>
                    <option value="gemini-1.5-flash">Gemini 1.5 Flash</option>
                    <option value="gpt-4o">GPT-4o</option>
                    <option value="claude-3-5-sonnet">Claude 3.5 Sonnet</option>
                </select>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Temperature</label>
                    <input type="number" id="ca-temperature" class="form-control" step="0.1" min="0" max="2" placeholder="0.7" style="font-size: 0.83rem;">
                    <div class="text-muted mt-1" style="font-size: 0.68rem; font-family: 'JetBrains Mono';">0 = precise · 2 = creative</div>
                </div>
                <div class="col-6">
                    <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Max Tokens</label>
                    <input type="number" id="ca-max-tokens" class="form-control" placeholder="8192" style="font-size: 0.83rem;">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">System Prompt</label>
                <textarea id="ca-system-prompt" class="form-control" rows="6" placeholder="You are..." style="font-family: 'JetBrains Mono'; font-size: 0.75rem; resize: vertical;"></textarea>
            </div>
            <div class="mb-4">
                <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Custom Guidelines</label>
                <textarea id="ca-guidelines" class="form-control" rows="3" placeholder="Additional behavioral guidelines..." style="font-family: 'JetBrains Mono'; font-size: 0.75rem; resize: vertical;"></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-1" id="btn-save-agent-config" style="font-size: 0.82rem;">
                    <i class="fa-solid fa-check me-1"></i> Save Configuration
                </button>
                <button type="button" class="btn" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-secondary); font-size: 0.82rem;" id="btn-reset-agent">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ CREATE AGENT OFFCANVAS ═══ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="createAgentDrawer" style="width: 440px;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title d-flex align-items-center gap-2">
            <i class="fa-solid fa-plus" style="color: var(--nexus-blue);"></i>
            New Agent
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="create-agent-form">
            @csrf
            <div class="mb-3">
                <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Agent Name *</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Souly Communicator" required>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Role *</label>
                <input type="text" name="role" class="form-control" placeholder="e.g. Communication Agent" required>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Model *</label>
                <select name="model" class="form-select">
                    <option value="gemini-2.0-flash">Gemini 2.0 Flash</option>
                    <option value="gemini-2.0-pro">Gemini 2.0 Pro</option>
                    <option value="gpt-4o">GPT-4o</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">System Prompt</label>
                <textarea name="system_prompt" class="form-control" rows="5" placeholder="You are..." style="font-family: 'JetBrains Mono'; font-size: 0.75rem;"></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100" style="font-size: 0.82rem;">
                <i class="fa-solid fa-robot me-1"></i> Create Agent
            </button>
        </form>
    </div>
</div>

{{-- ═══ EXECUTION LOG MODAL ═══ --}}
<div class="modal fade" id="execModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <span class="agent-status-orb busy" style="width: 8px; height: 8px; animation: pulse-glow 1s infinite;"></span>
                    Executing: <span id="exec-agent-name" class="ms-1 text-primary"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="exec-terminal" id="exec-terminal">
                    <span class="exec-line exec-line-info">[SYS] Initializing execution environment...</span>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm" style="background: rgba(255,255,255,0.05); border: 1px solid var(--error); color: var(--error); border-radius: 7px; font-size: 0.78rem;" id="btn-stop-exec">
                    <i class="fa-solid fa-stop me-1"></i> Stop
                </button>
                <button type="button" class="btn btn-sm" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-secondary); border-radius: 7px; font-size: 0.78rem;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {

    // ─── Open Configure Drawer ───
    window.openAgentDrawer = function(btn) {
        const agent = JSON.parse($(btn).data('agent'));
        $('#drawer-agent-name').text('Configure: ' + agent.name);
        $('#ca-agent-id').val(agent.id);
        $('#ca-model').val(agent.model || 'gemini-2.0-flash');
        $('#ca-temperature').val(agent.temperature || 0.7);
        $('#ca-max-tokens').val(agent.max_tokens || 8192);
        $('#ca-system-prompt').val(agent.system_prompt || '');
        $('#ca-guidelines').val(agent.guidelines || '');
        new bootstrap.Offcanvas(document.getElementById('configureAgentDrawer')).show();
    };

    // ─── Save Configuration ───
    $('#configure-agent-form').on('submit', function(e) {
        e.preventDefault();
        const id = $('#ca-agent-id').val();
        const $btn = $('#btn-save-agent-config');
        $btn.html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...').prop('disabled', true);

        $.ajax({
            url: `/hub/agents/${id}/toggle`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status: 'active',
                model: $('#ca-model').val(),
                temperature: $('#ca-temperature').val(),
                max_tokens: $('#ca-max-tokens').val(),
                system_prompt: $('#ca-system-prompt').val(),
                guidelines: $('#ca-guidelines').val(),
            },
            success: function() {
                Nexus.notify('Agent configuration saved!', 'success');
                $btn.html('<i class="fa-solid fa-check me-1"></i> Saved!');
                setTimeout(() => {
                    $btn.html('<i class="fa-solid fa-check me-1"></i> Save Configuration').prop('disabled', false);
                }, 2000);
            },
            error: function() {
                Nexus.notify('Failed to save configuration.', 'error');
                $btn.html('<i class="fa-solid fa-check me-1"></i> Save Configuration').prop('disabled', false);
            }
        });
    });

    // ─── Reset Agent ───
    $('#btn-reset-agent').on('click', function() {
        $('#ca-temperature').val(0.7);
        $('#ca-max-tokens').val(8192);
        $('#ca-system-prompt').val('');
        $('#ca-guidelines').val('');
        Nexus.notify('Configuration reset to defaults.', 'info');
    });

    // ─── Create Agent ───
    $('#create-agent-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("hub.agents.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    Nexus.notify('Agent created successfully!', 'success');
                    setTimeout(() => window.location.reload(), 800);
                }
            },
            error: function() { Nexus.notify('Failed to create agent.', 'error'); }
        });
    });

    // ─── Execute Agent ───
    window.executeAgent = function(id, name) {
        $('#exec-agent-name').text(name);
        $('#exec-terminal').html('<span class="exec-line exec-line-info">[SYS] Initializing execution for: ' + name + '...</span>');
        $('#execModal').modal('show');

        Nexus.updateStatusBar('Executing: ' + name, 'running');

        const logs = [
            { type: 'info',    msg: '[AGENT] Loading system prompt and context...' },
            { type: 'info',    msg: '[AGENT] Connecting to AI provider...' },
            { type: 'success', msg: '[AGENT] Provider connected: Gemini 2.0 Flash' },
            { type: 'info',    msg: '[AGENT] Injecting memory context...' },
            { type: 'info',    msg: '[AGENT] Processing task queue (3 tasks)...' },
            { type: 'success', msg: '[AGENT] Task 1/3 completed.' },
            { type: 'success', msg: '[AGENT] Task 2/3 completed.' },
            { type: 'success', msg: '[AGENT] Task 3/3 completed.' },
            { type: 'success', msg: '[AGENT] Execution complete. 0 errors.' },
        ];

        let i = 0;
        const interval = setInterval(() => {
            if (i >= logs.length) {
                clearInterval(interval);
                Nexus.updateStatusBar('Agent ' + name + ' done', 'success');
                setTimeout(() => Nexus.clearStatusBar(), 3000);
                return;
            }
            const log = logs[i++];
            const $terminal = $('#exec-terminal');
            $terminal.append('<span class="exec-line exec-line-' + log.type + '">' + log.msg + '</span>');
            $terminal.scrollTop($terminal[0].scrollHeight);
        }, 600);

        // Subscribe to real-time agent events
        window.Echo.channel(`agent.${id}`)
            .listen('AgentTaskCompleted', (e) => {
                const $terminal = $('#exec-terminal');
                $terminal.append(`<span class="exec-line exec-line-success">[REALTIME] ${e.message}</span>`);
                $terminal.scrollTop($terminal[0].scrollHeight);
            });
    };

    // ─── Toggle Agent Status ───
    $('.agent-toggle-btn').on('click', function() {
        const id = $(this).data('agent-id');
        const current = $(this).data('current-status');
        const newStatus = (current === 'active') ? 'inactive' : 'active';
        const $btn = $(this);

        $.ajax({
            url: `/hub/agents/${id}/toggle`,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content'), status: newStatus },
            success: function() {
                Nexus.notify(`Agent ${newStatus === 'active' ? 'activated' : 'deactivated'}.`, 'success');
                setTimeout(() => window.location.reload(), 500);
            }
        });
    });

});
</script>
@endpush
