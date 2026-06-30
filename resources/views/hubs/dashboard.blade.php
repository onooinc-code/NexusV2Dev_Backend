@extends('layouts.app')
@section('page_title', 'NexusHub')

@push('styles')
<style>
.health-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-family: 'JetBrains Mono', monospace;
    font-weight: 500;
    letter-spacing: 0.3px;
    border: 1px solid transparent;
}
.health-ok  { background: hsla(142,72%,29%,0.15); border-color: hsla(142,72%,29%,0.35); color: hsl(142,76%,60%); }
.health-warn { background: var(--amber-dim); border-color: hsla(38,92%,50%,0.35); color: hsl(38,92%,65%); }
.health-err  { background: var(--error-dim); border-color: hsla(0,84%,60%,0.35); color: hsl(0,84%,70%); }

.metric-card {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    padding: 22px 24px;
    position: relative;
    overflow: hidden;
    transition: all 0.25s var(--ease-smooth);
    height: 100%;
}
.metric-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    background: var(--accent-color, var(--nexus-blue));
}
.metric-card::after {
    content: '';
    position: absolute;
    top: -30px; right: -30px;
    width: 80px; height: 80px;
    border-radius: 50%;
    background: var(--accent-color, var(--nexus-blue));
    opacity: 0.05;
    filter: blur(20px);
}
.metric-card:hover {
    transform: translateY(-3px);
    border-color: var(--glass-border-hover);
    box-shadow: 0 12px 32px rgba(0,0,0,0.5);
}
.metric-value {
    font-family: 'Outfit', sans-serif;
    font-size: 2.2rem;
    font-weight: 700;
    line-height: 1;
    color: var(--text-primary);
    transition: color 0.5s ease;
}
.metric-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-muted);
    font-family: 'JetBrains Mono', monospace;
    margin-bottom: 8px;
}
.metric-icon {
    position: absolute;
    top: 20px; right: 20px;
    width: 36px; height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

/* Telemetry Feed */
.telemetry-feed {
    background: hsl(224, 71%, 2%);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    height: 240px;
    overflow-y: auto;
    padding: 12px 16px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.72rem;
    line-height: 1.8;
}
.telemetry-feed::-webkit-scrollbar { width: 3px; }
.telemetry-feed::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.06); }

.tele-line { display: flex; gap: 12px; }
.tele-time { color: var(--text-muted); flex-shrink: 0; }
.tele-level-INFO    { color: var(--nexus-teal); }
.tele-level-SUCCESS { color: hsl(142,76%,55%); }
.tele-level-WARN    { color: var(--amber); }
.tele-level-ERROR   { color: var(--error); }
.tele-msg { color: var(--text-secondary); }

/* AI Chat */
.ai-chat-area {
    height: 280px;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ai-chat-area::-webkit-scrollbar { width: 3px; }
.ai-chat-area::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.06); }

.chat-bubble {
    max-width: 80%;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 0.82rem;
    line-height: 1.6;
}
.chat-bubble.user {
    margin-left: auto;
    background: var(--nexus-blue-dim);
    border: 1px solid var(--nexus-blue-glow);
    color: var(--text-primary);
}
.chat-bubble.assistant {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--glass-border);
    color: var(--text-primary);
}
.chat-bubble.thinking {
    background: rgba(255,255,255,0.03);
    border: 1px dashed var(--glass-border);
    color: var(--text-muted);
    font-style: italic;
    font-size: 0.75rem;
}

/* Section Panel */
.section-panel {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    overflow: hidden;
}
.section-panel-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--glass-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.section-panel-header h6 {
    font-family: 'Outfit', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}
.section-panel-body { padding: 16px 20px; }
</style>
@endpush

@section('content')
<div class="d-flex flex-column gap-4 animate-in">

    {{-- ═══ SYSTEM HEALTH STRIP ═══ --}}
    <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 rounded-3" style="background: rgba(255,255,255,0.02); border: 1px solid var(--glass-border);">
        <span style="font-size: 0.72rem; font-family: 'JetBrains Mono'; color: var(--text-muted); letter-spacing: 1px; text-transform: uppercase; margin-right: 4px;">System Health</span>
        <div class="vr" style="background: var(--glass-border); height: 14px; margin: 0 8px;"></div>
        <span class="health-badge health-ok" id="hb-db"><i class="fa-solid fa-database" style="font-size: 0.6rem;"></i> Database: Online</span>
        <span class="health-badge health-ok" id="hb-redis"><i class="fa-solid fa-bolt" style="font-size: 0.6rem;"></i> Redis: Optimal</span>
        <span class="health-badge health-warn" id="hb-ai"><i class="fa-solid fa-microchip" style="font-size: 0.6rem;"></i> AI Models: Active</span>
        <span class="health-badge health-ok" id="hb-waha"><i class="fa-brands fa-whatsapp" style="font-size: 0.6rem;"></i> WAHA: Connected</span>
        <span class="health-badge health-ok" id="hb-queue"><i class="fa-solid fa-layer-group" style="font-size: 0.6rem;"></i> Queue: Running</span>
        <div class="ms-auto d-flex gap-2">
            <a href="{{ route('hub.waha') }}" class="btn btn-sm" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); color: var(--text-secondary); font-size: 0.75rem; padding: 4px 12px; border-radius: 6px;">
                <i class="fa-solid fa-rotate-right me-1"></i> Sync WAHA
            </a>
            <button class="btn btn-sm btn-primary" id="btn-new-chat" style="font-size: 0.75rem; padding: 4px 12px; border-radius: 6px;">
                <i class="fa-solid fa-plus me-1"></i> New Session
            </button>
        </div>
    </div>

    {{-- ═══ HERO HEADER ═══ --}}
    <div class="stagger-1" style="opacity: 0;">
        <div class="d-flex align-items-center gap-3 mb-1">
            <div style="width: 42px; height: 42px; background: var(--nexus-blue-dim); border: 1px solid var(--nexus-blue-glow); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-chart-line" style="color: var(--nexus-blue); font-size: 1.1rem;"></i>
            </div>
            <div>
                <h1 class="mb-0" style="font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em;">NexusHub
                    <span style="font-size: 0.65rem; font-family: 'JetBrains Mono'; color: var(--nexus-teal); background: var(--nexus-teal-dim); border: 1px solid hsla(174,90%,41%,0.3); padding: 2px 8px; border-radius: 4px; vertical-align: middle; margin-left: 8px; letter-spacing: 1px; text-transform: uppercase;">Live</span>
                </h1>
                <p class="text-muted mb-0" style="font-size: 0.8rem;">Central Intelligence Console & Cognitive Command Center</p>
            </div>
        </div>
    </div>

    {{-- ═══ KEY METRICS ROW ═══ --}}
    <div class="row g-3 stagger-2" style="opacity: 0;">
        <div class="col-6 col-md-3">
            <div class="metric-card" style="--accent-color: hsl(217,91%,60%);">
                <div class="metric-icon" style="background: var(--nexus-blue-dim);">
                    <i class="fa-solid fa-users" style="color: var(--nexus-blue);"></i>
                </div>
                <div class="metric-label">Total Contacts</div>
                <div class="metric-value" id="metric-contacts">{{ number_format($totalContacts ?? 0) }}</div>
                <div class="mt-2" style="font-size: 0.7rem; color: var(--text-muted);">
                    <i class="fa-solid fa-arrow-trend-up me-1" style="color: var(--success-bright);"></i>
                    <span id="trend-contacts">+{{ $contactDelta ?? 0 }} new today</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="metric-card" style="--accent-color: hsl(142,76%,45%);">
                <div class="metric-icon" style="background: hsla(142,72%,29%,0.15);">
                    <i class="fa-solid fa-list-check" style="color: var(--success-bright);"></i>
                </div>
                <div class="metric-label">Active Tasks</div>
                <div class="metric-value" id="metric-tasks">{{ number_format(($activeExecutes ?? 0) + ($activeTasksCount ?? 0)) }}</div>
                <div class="mt-2" style="font-size: 0.7rem; color: var(--text-muted);">
                    <i class="fa-solid fa-spinner fa-spin me-1" style="color: var(--nexus-teal);"></i>
                    {{ $activeExecutes ?? 0 }} workflows, {{ $activeTasksCount ?? 0 }} tasks
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="metric-card" style="--accent-color: hsl(258,90%,66%);">
                <div class="metric-icon" style="background: hsla(258,90%,66%,0.12);">
                    <i class="fa-solid fa-robot" style="color: var(--nexus-purple);"></i>
                </div>
                <div class="metric-label">AI Agents</div>
                <div class="metric-value" id="metric-agents">{{ number_format($agentCount ?? 0) }}</div>
                <div class="mt-2" style="font-size: 0.7rem; color: var(--text-muted);">
                    <span class="agent-status-orb online" style="width: 5px; height: 5px;"></span>
                    <span id="trend-agents">{{ $onlineAgentsCount ?? 0 }} active of {{ $totalAgentsCount ?? 0 }} total</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="metric-card" style="--accent-color: hsl(174,90%,41%);">
                <div class="metric-icon" style="background: var(--nexus-teal-dim);">
                    <i class="fa-solid fa-brain" style="color: var(--nexus-teal);"></i>
                </div>
                <div class="metric-label">Memory Entries</div>
                <div class="metric-value" id="metric-memory">{{ number_format($memoryCount ?? 0) }}</div>
                <div class="mt-2" style="font-size: 0.7rem; color: var(--text-muted);">
                    <i class="fa-solid fa-database me-1" style="color: var(--nexus-teal);"></i>
                    <span id="trend-memory">+{{ $memoryDelta ?? 0 }} new today</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ MAIN GRID: Telemetry + AI Console ═══ --}}
    <div class="row g-4 stagger-3" style="opacity: 0;">

        {{-- Live Telemetry Feed --}}
        <div class="col-12 col-lg-5">
            <div class="section-panel h-100">
                <div class="section-panel-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-terminal" style="color: var(--nexus-teal); font-size: 0.85rem;"></i>
                        <h6>System Telemetry</h6>
                        <span class="agent-status-orb online" style="width: 6px; height: 6px;"></span>
                    </div>
                    <div class="d-flex gap-2">
                        <button id="btn-pause-feed" class="btn btn-sm" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); color: var(--text-muted); font-size: 0.7rem; padding: 3px 10px; border-radius: 5px;">
                            <i class="fa-solid fa-pause me-1"></i> Pause
                        </button>
                        <button id="btn-clear-feed" class="btn btn-sm" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); color: var(--text-muted); font-size: 0.7rem; padding: 3px 10px; border-radius: 5px;">
                            <i class="fa-solid fa-broom me-1"></i> Clear
                        </button>
                    </div>
                </div>
                <div class="section-panel-body p-0">
                    <div class="telemetry-feed" id="telemetry-feed">
                        @forelse($recentLogs ?? [] as $log)
                        <div class="tele-line">
                            <span class="tele-time">{{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }}</span>
                            <span class="tele-level-{{ strtoupper($log->level ?? 'INFO') }}">[{{ strtoupper($log->level ?? 'INFO') }}]</span>
                            <span class="tele-msg">{{ Str::limit($log->message ?? 'System event', 80) }}</span>
                        </div>
                        @empty
                        <div class="tele-line" id="telemetry-empty">
                            <span class="tele-time">{{ now()->format('H:i:s') }}</span>
                            <span class="tele-level-INFO">[INFO]</span>
                            <span class="tele-msg">Waiting for system logs...</span>
                        </div>
                        @endforelse
                        <div class="tele-line mt-1">
                            <span class="tele-time">—</span>
                            <span class="tele-level-INFO">[SYS]</span>
                            <span class="tele-msg">Stream active <span class="cursor"></span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Central AI Console --}}
        <div class="col-12 col-lg-7">
            <div class="section-panel h-100 d-flex flex-column">
                <div class="section-panel-header">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 28px; height: 28px; background: var(--nexus-blue-dim); border: 1px solid var(--nexus-blue-glow); border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-robot" style="color: var(--nexus-blue); font-size: 0.75rem;"></i>
                        </div>
                        <h6>Central AI Console</h6>
                        <span style="font-size: 0.6rem; font-family: 'JetBrains Mono'; color: var(--nexus-teal); background: var(--nexus-teal-dim); border: 1px solid hsla(174,90%,41%,0.3); padding: 1px 6px; border-radius: 4px;">{{ $activeAgentModel }}</span>
                    </div>
                    <button id="btn-clear-chat" class="btn btn-sm" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); color: var(--text-muted); font-size: 0.7rem; padding: 3px 10px; border-radius: 5px;">
                        <i class="fa-solid fa-trash-can me-1"></i> Clear
                    </button>
                </div>

                <div class="ai-chat-area" id="ai-chat-area">
                    <div class="chat-bubble assistant">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="fa-solid fa-robot" style="font-size: 0.7rem; color: var(--nexus-blue);"></i>
                            <span style="font-size: 0.65rem; color: var(--text-muted); font-family: 'JetBrains Mono';">Souly · Nexus AI</span>
                        </div>
                        Hello, {{ auth()->user()->name ?? 'User' }}. I'm Souly — your Nexus cognitive assistant. I have access to your contacts, tasks, workflows, and system metrics. How can I help you today?
                    </div>
                </div>

                <div class="chat-composer p-3 border-top" style="border-color: var(--glass-border) !important;">
                    <div class="d-flex gap-2 align-items-end">
                        <textarea id="ai-input" class="form-control composer-input" placeholder="Ask Souly anything... (⌘+Enter to send)" rows="1" style="resize: none; background: rgba(6,11,19,0.8) !important; font-size: 0.83rem;"></textarea>
                        <button id="btn-send-ai" class="btn btn-primary flex-shrink-0" style="border-radius: 8px; padding: 8px 16px; font-size: 0.82rem;">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between mt-2" style="font-size: 0.68rem; color: var(--text-muted);">
                        <span><i class="fa-solid fa-circle me-1" style="font-size: 0.4rem; color: var(--nexus-teal);"></i>System context injected</span>
                        <span id="ai-token-count">0 tokens</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ BOTTOM GRID: Recent Contacts + Quick Stats + Agent Status ═══ --}}
    <div class="row g-4 stagger-4" style="opacity: 0;">

        {{-- Recent Contacts --}}
        <div class="col-12 col-lg-4">
            <div class="section-panel h-100">
                <div class="section-panel-header">
                    <h6><i class="fa-solid fa-users me-2" style="color: var(--nexus-blue); font-size: 0.8rem;"></i>Recent Contacts</h6>
                    <a href="{{ route('hub.contacts') }}" style="font-size: 0.72rem; color: var(--nexus-blue); text-decoration: none;">View all →</a>
                </div>
                <div class="section-panel-body p-0">
                    @forelse($recentContacts ?? [] as $contact)
                    <a href="{{ route('hub.contacts.profile', $contact->id) }}" class="d-flex align-items-center gap-3 px-4 py-3 text-decoration-none" style="border-bottom: 1px solid var(--glass-border); transition: background 0.15s ease;" onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background='transparent'">
                        <div style="width: 34px; height: 34px; border-radius: 50%; background: var(--nexus-blue-dim); border: 1px solid var(--nexus-blue-glow); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 600; color: var(--nexus-blue); font-size: 0.85rem;">
                            {{ strtoupper(substr($contact->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="fw-medium text-light" style="font-size: 0.83rem;">{{ $contact->name }}</div>
                            <div class="text-muted truncate" style="font-size: 0.72rem;">{{ $contact->role ?? $contact->company ?? 'No role' }}</div>
                        </div>
                        <div>
                            <span class="reply-mode-badge {{ $contact->reply_mode_override ?? 'manual' }}">
                                {{ $contact->reply_mode_override ?? 'manual' }}
                            </span>
                        </div>
                    </a>
                    @empty
                    <div class="px-4 py-5 text-center text-muted" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-users-slash mb-2 d-block" style="font-size: 1.5rem; color: var(--glass-border);"></i>
                        No contacts yet
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Agent Status --}}
        <div class="col-12 col-lg-4">
            <div class="section-panel h-100">
                <div class="section-panel-header">
                    <h6><i class="fa-solid fa-robot me-2" style="color: var(--nexus-purple); font-size: 0.8rem;"></i>Agent Status</h6>
                    <a href="{{ route('hub.agents') }}" style="font-size: 0.72rem; color: var(--nexus-blue); text-decoration: none;">Manage →</a>
                </div>
                <div class="section-panel-body">
                    @forelse($agents ?? [] as $agent)
                    <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom: 1px solid var(--glass-border);">
                        <div class="d-flex align-items-center gap-2">
                            <span class="agent-status-orb agent-orb-{{ $agent->id }} {{ $agent->status === 'active' ? 'online' : ($agent->status === 'busy' ? 'busy' : 'offline') }}"></span>
                            <span style="font-size: 0.83rem; color: var(--text-primary);">{{ $agent->name }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span style="font-size: 0.68rem; color: var(--text-muted); font-family: 'JetBrains Mono';">{{ $agent->model ?? 'gemini' }}</span>
                            <span class="nx-tag agent-tag-{{ $agent->id }}">{{ $agent->status ?? 'offline' }}</span>
                            
                            <!-- Agent Action Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-link btn-sm text-muted p-0" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--text-muted) !important; border: none; background: transparent; box-shadow: none;">
                                    <i class="fa-solid fa-ellipsis-vertical" style="font-size: 0.8rem;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="background: rgba(9,15,25,0.97); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 8px; z-index: 1050;">
                                    <li>
                                        <a class="dropdown-item rounded text-light" href="{{ route('hub.agents') }}" style="font-size: 0.8rem; padding: 6px 12px;">
                                            <i class="fa-solid fa-sliders me-2 text-muted"></i>View Settings
                                        </a>
                                    </li>
                                    <li>
                                        <button class="dropdown-item rounded text-light btn-restart-agent" data-agent-id="{{ $agent->id }}" data-agent-name="{{ $agent->name }}" style="font-size: 0.8rem; padding: 6px 12px; border: none; background: transparent; text-align: left; width: 100%;">
                                            <i class="fa-solid fa-rotate-right me-2 text-muted"></i>Restart Agent
                                        </button>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded text-light" href="{{ route('hub.logs') }}" style="font-size: 0.8rem; padding: 6px 12px;">
                                            <i class="fa-solid fa-terminal me-2 text-muted"></i>View Logs
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="py-4 text-center text-muted" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-robot mb-2 d-block" style="font-size: 1.5rem; color: var(--glass-border);"></i>
                        No agents configured
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Quick Stats / Upcoming --}}
        <div class="col-12 col-lg-4">
            <div class="section-panel h-100">
                <div class="section-panel-header">
                    <h6><i class="fa-solid fa-bolt me-2" style="color: var(--amber); font-size: 0.8rem;"></i>Upcoming Schedules</h6>
                    <a href="{{ route('hub.scheduler') }}" style="font-size: 0.72rem; color: var(--nexus-blue); text-decoration: none;">View all →</a>
                </div>
                <div class="section-panel-body">
                    @forelse($upcomingSchedules ?? [] as $schedule)
                    <div class="d-flex align-items-center gap-3 py-2" style="border-bottom: 1px solid var(--glass-border);">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--amber-dim); border: 1px solid hsla(38,92%,50%,0.25); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa-regular fa-clock" style="color: var(--amber); font-size: 0.75rem;"></i>
                        </div>
                        <div class="flex-1">
                            <div style="font-size: 0.82rem; color: var(--text-primary);">{{ Str::limit($schedule->name ?? 'Schedule', 30) }}</div>
                            <div style="font-size: 0.68rem; color: var(--text-muted); font-family: 'JetBrains Mono';">{{ $schedule->cron_expression ?? '—' }} · {{ optional($schedule->next_run_at)->diffForHumans() ?? 'Not scheduled' }}</div>
                        </div>
                        <span class="nx-tag {{ $schedule->is_active ? 'text-success' : '' }}">{{ $schedule->is_active ? 'active' : 'paused' }}</span>
                    </div>
                    @empty
                    <div class="py-4 text-center text-muted" style="font-size: 0.8rem;">
                        <i class="fa-regular fa-clock mb-2 d-block" style="font-size: 1.5rem; color: var(--glass-border);"></i>
                        No upcoming schedules
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // ─── Telemetry Feed ───
    let feedPaused = false;
    const $feed = $('#telemetry-feed');

    function scrollFeedToBottom() {
        if (!feedPaused) $feed.scrollTop($feed[0].scrollHeight);
    }
    scrollFeedToBottom();

    $('#btn-pause-feed').on('click', function() {
        feedPaused = !feedPaused;
        const $btn = $(this);
        if (feedPaused) {
            $btn.html('<i class="fa-solid fa-play me-1"></i> Resume');
        } else {
            $btn.html('<i class="fa-solid fa-pause me-1"></i> Pause');
            scrollFeedToBottom();
        }
    });

    $('#btn-clear-feed').on('click', function() {
        $feed.html('<div class="tele-line"><span class="tele-time">' + new Date().toTimeString().slice(0,8) + '</span><span class="tele-level-INFO">[SYS]</span><span class="tele-msg">Feed cleared. <span class="cursor"></span></span></div>');
    });

    // Listen for log events via Echo
    window.Echo.channel('nexus-system')
        .listen('NewActivityLog', (e) => {
            if (feedPaused) return;
            const time = new Date().toTimeString().slice(0,8);
            const level = (e.level || 'INFO').toUpperCase();
            const levelClass = { INFO: 'tele-level-INFO', SUCCESS: 'tele-level-SUCCESS', WARNING: 'tele-level-WARN', ERROR: 'tele-level-ERROR' }[level] || 'tele-level-INFO';
            const line = `<div class="tele-line animate-in"><span class="tele-time">${time}</span><span class="${levelClass}">[${level}]</span><span class="tele-msg">${e.message}</span></div>`;
            $feed.find('.tele-line:last').before(line);
            // Remove cursor and re-add
            $feed.find('.cursor').remove();
            $feed.append('<div class="tele-line"><span class="tele-time">—</span><span class="tele-level-INFO">[SYS]</span><span class="tele-msg">Stream active <span class="cursor"></span></span></div>');
            scrollFeedToBottom();
        });

    // Health Strip polling
    function updateHealthStrip() {
        $.ajax({
            url: '{{ route("hub.dashboard.health") }}',
            method: 'GET',
            global: false,
            success: function(data) {
                if (data && data.services) {
                    data.services.forEach(service => {
                        let statusClass = 'health-err';
                        let statusText = 'Offline';
                        if (service.status === 'online') {
                            statusClass = 'health-ok';
                            statusText = 'Online';
                        } else if (service.status === 'degraded') {
                            statusClass = 'health-warn';
                            statusText = 'Degraded';
                        }
                        
                        let badgeId = '';
                        let iconHtml = '';
                        if (service.name === 'Database') {
                            badgeId = '#hb-db';
                            iconHtml = '<i class="fa-solid fa-database" style="font-size: 0.6rem;"></i>';
                            $(badgeId).attr('class', 'health-badge ' + statusClass).html(iconHtml + ' Database: ' + statusText);
                        } else if (service.name === 'Redis Cache') {
                            badgeId = '#hb-redis';
                            iconHtml = '<i class="fa-solid fa-bolt" style="font-size: 0.6rem;"></i>';
                            let optText = service.status === 'online' ? 'Optimal' : statusText;
                            $(badgeId).attr('class', 'health-badge ' + statusClass).html(iconHtml + ' Redis: ' + optText);
                        } else if (service.name === 'AI Models') {
                            badgeId = '#hb-ai';
                            iconHtml = '<i class="fa-solid fa-microchip" style="font-size: 0.6rem;"></i>';
                            let activeText = service.status === 'online' ? 'Active' : statusText;
                            $(badgeId).attr('class', 'health-badge ' + statusClass).html(iconHtml + ' AI Models: ' + activeText);
                        } else if (service.name === 'WAHA') {
                            badgeId = '#hb-waha';
                            iconHtml = '<i class="fa-brands fa-whatsapp" style="font-size: 0.6rem;"></i>';
                            let connText = service.status === 'online' ? 'Connected' : statusText;
                            $(badgeId).attr('class', 'health-badge ' + statusClass).html(iconHtml + ' WAHA: ' + connText);
                        } else if (service.name === 'Queue Worker') {
                            badgeId = '#hb-queue';
                            iconHtml = '<i class="fa-solid fa-layer-group" style="font-size: 0.6rem;"></i>';
                            let runText = service.status === 'online' ? 'Running' : statusText;
                            $(badgeId).attr('class', 'health-badge ' + statusClass).html(iconHtml + ' Queue: ' + runText);
                        }
                    });
                }
            },
            error: function(err) {
                console.error('Failed to fetch health status', err);
            }
        });
    }
    updateHealthStrip();
    setInterval(updateHealthStrip, 15000);

    // Activity feed polling
    function fetchActivityFeed() {
        if (feedPaused) return;
        $.ajax({
            url: '{{ route("hub.dashboard.activity-feed") }}?limit=20',
            method: 'GET',
            global: false,
            success: function(res) {
                if (res && res.data && res.data.length > 0) {
                    $('#telemetry-empty').remove();
                    
                    let html = '';
                    res.data.reverse().forEach(log => {
                        const time = new Date(log.created_at).toTimeString().slice(0,8);
                        const level = (log.severity || 'info').toUpperCase();
                        const levelClass = { INFO: 'tele-level-INFO', WARNING: 'tele-level-WARN', ERROR: 'tele-level-ERROR' }[level] || 'tele-level-INFO';
                        html += `<div class="tele-line"><span class="tele-time">${time}</span><span class="${levelClass}">[${level}]</span><span class="tele-msg">${log.message}</span></div>`;
                    });
                    
                    html += `<div class="tele-line"><span class="tele-time">—</span><span class="tele-level-INFO">[SYS]</span><span class="tele-msg">Stream active <span class="cursor"></span></span></div>`;
                    $feed.html(html);
                    scrollFeedToBottom();
                }
            },
            error: function(err) {
                console.error('Failed to fetch activity feed', err);
            }
        });
    }
    fetchActivityFeed();
    setInterval(fetchActivityFeed, 8000);

    // ─── AI Chat ───
    const $chatArea = $('#ai-chat-area');
    const $input = $('#ai-input');
    let tokenCount = 0;

    function scrollChat() {
        $chatArea.scrollTop($chatArea[0].scrollHeight);
    }

    function addMessage(text, role, actualTokens = null) {
        const ts = new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
        const icon = role === 'user' ? 'fa-user' : 'fa-robot';
        const label = role === 'user' ? 'You' : 'Souly · Nexus AI';
        const color = role === 'user' ? 'var(--nexus-blue)' : 'hsl(217,91%,60%)';
        const bubble = $(`
            <div class="chat-bubble ${role}" style="animation: fadeInSlideUp 0.3s ease">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="fa-solid ${icon}" style="font-size: 0.7rem; color: ${color};"></i>
                    <span style="font-size: 0.65rem; color: var(--text-muted); font-family: 'JetBrains Mono';">${label} · ${ts}</span>
                </div>
                ${text}
            </div>
        `);
        $chatArea.append(bubble);
        scrollChat();
        if (actualTokens !== null) {
            tokenCount += actualTokens;
        } else {
            tokenCount += Math.ceil(text.length / 4);
        }
        $('#ai-token-count').text(tokenCount + ' tokens');
    }

    function addThinking() {
        const thinking = $('<div class="chat-bubble thinking" id="thinking-bubble"><i class="fa-solid fa-circle-notch fa-spin me-2"></i>Souly is thinking...</div>');
        $chatArea.append(thinking);
        scrollChat();
        return thinking;
    }

    async function sendMessage() {
        const text = $input.val().trim();
        if (!text) return;
        $input.val('');
        addMessage(text, 'user');
        const $thinking = addThinking();

        try {
            const res = await $.ajax({
                url: '{{ route("hub.hedra-soul.message") }}',
                method: 'POST',
                data: JSON.stringify({ message: text, context: 'dashboard' }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });
            $thinking.remove();
            const actualReply = res.reply || res.message || 'Response received.';
            addMessage(actualReply, 'assistant', res.token_count);
        } catch(e) {
            $thinking.remove();
            addMessage('I encountered an issue processing your request. Please check the system logs.', 'assistant');
        }
    }

    $('#btn-send-ai').on('click', sendMessage);
    $input.on('keydown', function(e) {
        if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) sendMessage();
        // Auto-resize
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    $('#btn-clear-chat').on('click', function() {
        $chatArea.empty();
        addMessage('Chat cleared. How can I assist you?', 'assistant');
        tokenCount = 0;
        $('#ai-token-count').text('0 tokens');
    });

    // ─── Restart Agent Action ───
    $(document).on('click', '.btn-restart-agent', async function(e) {
        e.preventDefault();
        const $btn = $(this);
        const agentId = $btn.data('agent-id');
        const agentName = $btn.data('agent-name');
        
        // Show statusbar loading state
        const originalStatus = $('#statusbar-task-status').html();
        $('#statusbar-task-status').html('<i class="fa-solid fa-circle-notch fa-spin me-1" style="color: var(--nexus-blue); font-size: 0.65rem;"></i> Restarting agent ' + agentName + '...');
        
        // Temporarily change orb and tag
        const $orb = $('.agent-orb-' + agentId);
        const $tag = $('.agent-tag-' + agentId);
        const originalOrbClass = $orb.attr('class');
        const originalTagText = $tag.text();
        
        $orb.attr('class', 'agent-status-orb busy');
        $tag.text('restarting');
        
        try {
            const res = await $.ajax({
                url: '/hub/agents/' + agentId + '/restart',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });
            
            // Set to online/active
            $orb.attr('class', 'agent-status-orb online');
            $tag.text('active');
            
            $('#statusbar-task-status').html('<i class="fa-solid fa-circle-check me-1" style="color: var(--nexus-teal); font-size: 0.65rem;"></i> Agent ' + agentName + ' restarted');
            setTimeout(() => {
                $('#statusbar-task-status').html(originalStatus);
            }, 3000);
            
            // Add a temporary telemetry line
            const time = new Date().toTimeString().slice(0,8);
            const newLine = `<div class="tele-line animate-in" style="animation: fadeInSlideUp 0.3s ease"><span class="tele-time">${time}</span><span class="tele-level-SUCCESS">[SUCCESS]</span><span class="tele-msg">Agent '${agentName}' successfully restarted via console.</span></div>`;
            $feed.find('.tele-line:last').before(newLine);
            scrollFeedToBottom();
            
        } catch (err) {
            $orb.attr('class', originalOrbClass);
            $tag.text(originalTagText);
            
            $('#statusbar-task-status').html('<i class="fa-solid fa-circle-xmark me-1" style="color: var(--error); font-size: 0.65rem;"></i> Restart failed');
            setTimeout(() => {
                $('#statusbar-task-status').html(originalStatus);
            }, 3000);
            alert('Failed to restart agent: ' + (err.responseJSON?.message || 'Unknown error'));
        }
    });

    // ─── Metrics real-time update via Echo ───
    window.Echo.channel('nexus-system')
        .listen('MetricsUpdated', (e) => {
            if (e.total_contacts !== undefined) {
                $('#metric-contacts').text(Number(e.total_contacts).toLocaleString());
            }
            if (e.active_tasks !== undefined) {
                $('#metric-tasks').text(Number(e.active_tasks).toLocaleString());
            }
            if (e.agent_count !== undefined) {
                $('#metric-agents').text(Number(e.agent_count).toLocaleString());
            }
            if (e.memory_count !== undefined) {
                $('#metric-memory').text(Number(e.memory_count).toLocaleString());
            }
        });
});
</script>
@endpush
