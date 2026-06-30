@extends('layouts.app')

@push('styles')
<style>
    .soul-container {
        display: flex;
        height: calc(100vh - 150px);
        background: var(--nexus-panel);
        border: 1px solid var(--nexus-border);
        border-radius: 12px;
        overflow: hidden;
    }
    
    /* Left Column: Sessions */
    .soul-sessions {
        width: 250px;
        border-right: 1px solid var(--nexus-border);
        background: rgba(11, 14, 20, 0.5);
        display: flex;
        flex-direction: column;
    }
    .session-item {
        padding: 12px 15px;
        border-bottom: 1px solid var(--nexus-border);
        cursor: pointer;
        transition: background 0.2s;
    }
    .session-item:hover { background: rgba(255, 255, 255, 0.05); }
    .session-item.active { background: rgba(99, 102, 241, 0.15); border-left: 3px solid var(--nexus-secondary); }
    
    /* Center Column: Chat */
    .soul-chat {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: rgba(22, 27, 34, 0.3);
    }
    .chat-history {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .chat-bubble {
        max-width: 80%;
        padding: 12px 18px;
        border-radius: 12px;
        font-size: 0.95rem;
        line-height: 1.5;
    }
    .chat-bubble.user {
        align-self: flex-end;
        background: rgba(99, 102, 241, 0.2);
        border: 1px solid rgba(99, 102, 241, 0.4);
        border-bottom-right-radius: 2px;
    }
    .chat-bubble.agent {
        align-self: flex-start;
        background: rgba(0, 122, 255, 0.1);
        border: 1px solid rgba(0, 122, 255, 0.3);
        border-bottom-left-radius: 2px;
    }
    .chat-bubble.system {
        align-self: center;
        background: rgba(255, 255, 255, 0.05);
        border: 1px dashed rgba(255, 255, 255, 0.2);
        font-size: 0.8rem;
        color: var(--nexus-text-muted);
        text-align: center;
    }

    .chat-composer {
        padding: 15px;
        border-top: 1px solid var(--nexus-border);
        background: rgba(22, 27, 34, 0.8);
        backdrop-filter: blur(12px);
    }
    .composer-box {
        background: rgba(11, 14, 20, 0.8);
        border: 1px solid var(--nexus-border);
        border-radius: 8px;
    }
    .composer-textarea {
        background: transparent;
        border: none;
        color: white;
        resize: none;
        width: 100%;
        padding: 10px 15px;
        outline: none;
    }

    /* Right Column: Controls */
    .soul-controls {
        width: 300px;
        border-left: 1px solid var(--nexus-border);
        background: rgba(11, 14, 20, 0.5);
        padding: 15px;
        overflow-y: auto;
    }
    .control-group {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px dashed var(--nexus-border);
    }
    .control-group:last-child { border-bottom: none; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 text-light mb-1"><i class="fa-solid fa-ghost text-secondary me-2"></i> Hedra Soul</h2>
        <p class="text-muted small mb-0">Direct interaction and control panel for Souly AI.</p>
    </div>
    <div>
        <span class="badge bg-danger p-2"><i class="fa-solid fa-stop me-1"></i> Emergency Pause</span>
    </div>
</div>

<div class="soul-container animate-fade-in">
    <!-- Left: Sessions -->
    <div class="soul-sessions">
        <div class="p-3 border-bottom border-secondary">
            <button class="btn btn-outline-secondary w-100 btn-sm"><i class="fa-solid fa-plus me-1"></i> New Session</button>
        </div>
        <div class="session-list">
            @forelse($sessions as $session)
            <a href="?session_id={{ $session->id }}" class="text-decoration-none">
                <div class="session-item {{ isset($selectedSession) && $selectedSession->id == $session->id ? 'active' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-light text-truncate" style="max-width: 70%;">{{ $session->title }}</strong>
                        <span class="text-muted" style="font-size: 0.75rem;">{{ $session->updated_at->diffForHumans(null, true, true) }}</span>
                    </div>
                    <div class="text-muted small text-truncate">
                        <i class="fa-solid fa-list-check me-1"></i> {{ $session->task_count ?? 0 }} Tasks
                    </div>
                </div>
            </a>
            @empty
            <div class="p-3 text-center text-muted small border border-secondary border-dashed rounded m-2">No sessions found.</div>
            @endforelse
        </div>
    </div>

    <!-- Center: Chat -->
    <div class="soul-chat">
        <!-- Header -->
        <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center" style="background: rgba(22,27,34,0.8); backdrop-filter: blur(12px);">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-circle-dot text-success me-2 animate-pulse"></i>
                <span class="fw-bold">Session: API Integration Debug</span>
            </div>
            <div class="text-muted small">GPT-4o • Operator Mode</div>
        </div>

        <!-- History -->
        <div class="chat-history" id="soulChatHistory">
            <div class="chat-bubble system">
                Session started at 09:00 AM. Model initialized with "Operator" autonomy.
            </div>

            <div class="chat-bubble user">
                Souly, please check the logs for the Stripe webhook failure that happened around 2 AM.
            </div>

            <div class="chat-bubble agent">
                <div class="mb-2"><i class="fa-solid fa-magnifying-glass text-primary me-2"></i> <em>Searching logs for "Stripe webhook" between 01:00 and 03:00...</em></div>
                I found 3 errors related to Stripe webhooks. The primary error is a `SignatureVerificationException`. It seems the webhook secret configured in the environment variables might be incorrect or expired.
            </div>

            <div class="chat-bubble system">
                <i class="fa-solid fa-shield-halved text-warning me-1"></i> Souly requested read access to `/config/services.php`. Access granted automatically based on current Autonomy Mode.
            </div>

            <div class="chat-bubble agent">
                I've verified the config. The `STRIPE_WEBHOOK_SECRET` is currently missing from your `.env` file. Would you like me to guide you on how to set it up, or should I attempt to fetch it via the Stripe API CLI if you have it installed?
            </div>
        </div>

        <!-- Composer -->
        <div class="chat-composer">
            <div class="composer-box d-flex flex-column">
                <textarea class="composer-textarea" rows="2" placeholder="Instruct Souly..." id="soulInput"></textarea>
                <div class="d-flex justify-content-between align-items-center px-3 pb-2 pt-1">
                    <div class="text-muted" style="font-size: 0.75rem;"><i class="fa-brands fa-markdown me-1"></i> Markdown supported</div>
                    <button class="btn btn-sm btn-primary px-3 rounded-pill" onclick="sendSoulMessage()"><i class="fa-solid fa-paper-plane me-1"></i> Send</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Controls -->
    <div class="soul-controls">
        <h6 class="text-uppercase text-muted mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">Autonomy Mode</h6>
        
        <div class="control-group">
            <select class="form-select form-select-sm mb-2 bg-dark text-light border-secondary">
                <option value="chat_only">Chat Only (No Execution)</option>
                <option value="copilot">Copilot (Ask before execute)</option>
                <option value="operator" selected>Operator (Auto-execute safe)</option>
                <option value="autopilot">Autopilot (Full Autonomy)</option>
            </select>
            <p class="text-muted mt-2" style="font-size: 0.75rem;">Operator mode allows Souly to read files and APIs, but requires permission for destructive actions.</p>
        </div>

        <h6 class="text-uppercase text-muted mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">Permissions Matrix</h6>
        
        <div class="control-group">
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" checked disabled>
                <label class="form-check-label text-light" style="font-size: 0.85rem;">Global Memory Access</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" checked disabled>
                <label class="form-check-label text-light" style="font-size: 0.85rem;">Contact Data Read</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" disabled>
                <label class="form-check-label text-muted" style="font-size: 0.85rem;">Execute Workflows</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" disabled>
                <label class="form-check-label text-muted" style="font-size: 0.85rem;">External Messaging</label>
            </div>
        </div>

        <h6 class="text-uppercase text-muted mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">System Info</h6>
        <div class="control-group text-light" style="font-size: 0.8rem;">
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Model:</span>
                <span>gpt-4o-2024-05-13</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Context Window:</span>
                <span>128k</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Instruction Set:</span>
                <span>v2.4.1 (Nexus Core)</span>
            </div>
        </div>
    </div>
</div>
@endsection

@stack('scripts')
<script>
    function sendHedraMessage() {
        const input = document.getElementById('chatInput');
        const text = input.value.trim();
        if (!text) return;

        const history = document.getElementById('chatHistory');
        const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        const bubble = `
            <div class="message-bubble outgoing animate-fade-in">
                <div class="mb-1"><strong class="text-light" style="font-size: 0.75rem;"><i class="fa-solid fa-user me-1"></i> YOU</strong></div>
                ${text}
                <div class="text-end text-white-50 mt-2" style="font-size: 0.7rem;">${time}</div>
            </div>
        `;
        
        history.insertAdjacentHTML('beforeend', bubble);
        history.scrollTop = history.scrollHeight;
        input.value = '';

        @if($selectedSession)
        // Send to backend
        fetch('{{ route("hub.hedra-soul.message") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                session_id: {{ $selectedSession->id }},
                content: text
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Simulate Souly thinking then replying
                setTimeout(() => {
                    const reply = `
                        <div class="message-bubble incoming border-primary border animate-fade-in" style="background: rgba(13, 110, 253, 0.1);">
                            <div class="mb-1"><strong class="text-primary" style="font-size: 0.75rem;"><i class="fa-solid fa-robot me-1"></i> SOULY</strong></div>
                            ${data.reply.body}
                            <div class="text-end text-white-50 mt-2" style="font-size: 0.7rem;">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                        </div>
                    `;
                    history.insertAdjacentHTML('beforeend', reply);
                    history.scrollTop = history.scrollHeight;
                }, 1000);
            }
        });
        @endif
    }

    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('chatInput');
        if(input) {
            input.addEventListener('keypress', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendHedraMessage();
                }
            });
            const history = document.getElementById('chatHistory');
            if(history) history.scrollTop = history.scrollHeight;
        }
    });
</script>
