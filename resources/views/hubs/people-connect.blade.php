@extends('layouts.app')

@push('styles')
<style>
    .split-pane {
        display: flex;
        height: calc(100vh - 150px);
        background: var(--nexus-panel);
        border: 1px solid var(--nexus-border);
        border-radius: 12px;
        overflow: hidden;
    }
    .conversation-sidebar {
        width: 300px;
        border-right: 1px solid var(--nexus-border);
        display: flex;
        flex-direction: column;
        background: rgba(11, 14, 20, 0.4);
    }
    .conversation-list {
        flex-grow: 1;
        overflow-y: auto;
    }
    .conversation-item {
        padding: 15px;
        border-bottom: 1px solid var(--nexus-border);
        cursor: pointer;
        transition: background 0.2s;
    }
    .conversation-item:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    .conversation-item.active {
        background: rgba(0, 122, 255, 0.15);
        border-left: 3px solid var(--nexus-primary);
    }
    .message-area {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        background: rgba(22, 27, 34, 0.2);
    }
    .message-header {
        padding: 15px 20px;
        border-bottom: 1px solid var(--nexus-border);
        background: rgba(22, 27, 34, 0.8);
        backdrop-filter: blur(12px);
    }
    .message-history {
        flex-grow: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .message-bubble {
        max-width: 70%;
        padding: 10px 15px;
        border-radius: 15px;
        font-size: 0.9rem;
    }
    .message-bubble.incoming {
        align-self: flex-start;
        background: rgba(255, 255, 255, 0.1);
        border-bottom-left-radius: 0;
    }
    .message-bubble.outgoing {
        align-self: flex-end;
        background: var(--nexus-primary);
        border-bottom-right-radius: 0;
    }
    .message-bubble.ai-reply {
        align-self: flex-end;
        background: var(--nexus-secondary);
        border-bottom-right-radius: 0;
    }
    .message-composer {
        padding: 15px 20px;
        border-top: 1px solid var(--nexus-border);
        background: rgba(22, 27, 34, 0.8);
        backdrop-filter: blur(12px);
    }
    .composer-input {
        background: rgba(11, 14, 20, 0.6);
        border: 1px solid var(--nexus-border);
        color: var(--nexus-text);
        border-radius: 20px;
        resize: none;
    }
    .composer-input:focus {
        background: rgba(11, 14, 20, 0.9);
        border-color: var(--nexus-primary);
        box-shadow: none;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 text-light mb-1"><i class="fa-solid fa-users text-primary me-2"></i> People Connect</h2>
        <p class="text-muted small mb-0">Private communication and relationship management center.</p>
    </div>
    <div>
        <button class="btn btn-outline-primary btn-sm me-2" onclick="syncWaha()">
            <i class="fa-brands fa-whatsapp me-1"></i> Sync WAHA
        </button>
        <button class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> New Chat
        </button>
    </div>
</div>

<div class="split-pane animate-fade-in">
    <!-- Sidebar -->
    <div class="conversation-sidebar">
        <div class="p-3 border-bottom border-secondary">
            <input type="text" class="form-control form-control-sm" placeholder="Search contacts...">
        </div>
        <div class="conversation-list">
            @forelse($contacts as $contact)
            <a href="?contact_id={{ $contact->id }}" class="text-decoration-none">
                <div class="conversation-item {{ isset($selectedContact) && $selectedContact->id == $contact->id ? 'active' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-light">{{ $contact->name ?: 'Unknown' }}</strong>
                        <span class="text-muted" style="font-size: 0.75rem;">{{ $contact->updated_at->diffForHumans() }}</span>
                    </div>
                    <div class="text-muted small text-truncate">
                        {{ $contact->messages_count }} messages
                    </div>
                </div>
            </a>
            @empty
            <div class="p-3 text-center text-muted small">No contacts found.</div>
            @endforelse
        </div>
    </div>

    <!-- Main Message Area -->
    <div class="message-area">
        @if($selectedContact)
        <!-- Header -->
        <div class="message-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-primary text-white d-flex justify-content-center align-items-center me-3" style="width: 40px; height: 40px; font-weight: bold;">
                    {{ strtoupper(substr($selectedContact->name ?? 'U', 0, 2)) }}
                </div>
                <div>
                    <h6 class="text-light mb-0">{{ $selectedContact->name }}</h6>
                    <small class="text-success"><i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i> Active</small>
                </div>
            </div>
            <div>
                <span class="badge bg-secondary rounded-pill me-2">Autopilot: Off</span>
                <button class="btn btn-sm btn-outline-secondary border-0"><i class="fa-solid fa-ellipsis-vertical"></i></button>
            </div>
        </div>

        <!-- History -->
        <div class="message-history" id="messageHistory">
            @forelse($messages as $msg)
                @php
                    // Determine if incoming or outgoing. Adjust based on your table structure.
                    // Assume 'is_from_me' or similar exists. Fallback to alternating if unknown for demo.
                    $isOutgoing = $msg->is_from_me ?? false;
                @endphp
                <div class="message-bubble {{ $isOutgoing ? 'outgoing' : 'incoming' }}">
                    {{ $msg->content ?? $msg->body ?? $msg->text ?? 'No content' }}
                    <div class="text-end text-white-50 mt-1" style="font-size: 0.7rem;">
                        {{ $msg->created_at->format('h:i A') }}
                    </div>
                </div>
            @empty
                <div class="text-center text-muted small my-3">No messages in this conversation yet.</div>
            @endforelse
        </div>

        <!-- Composer -->
        <div class="message-composer">
            <div class="d-flex align-items-end">
                <button class="btn btn-outline-secondary border-0 text-muted me-2 mb-1"><i class="fa-solid fa-paperclip"></i></button>
                <textarea class="form-control composer-input" rows="1" placeholder="Type a message..." id="messageInput"></textarea>
                <button class="btn btn-primary rounded-circle ms-2 mb-1" style="width: 40px; height: 40px;" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
            <div class="text-muted mt-2 text-center" style="font-size: 0.7rem;">Press Enter to send, Shift+Enter for new line</div>
        </div>
        @else
        <div class="h-100 d-flex flex-column justify-content-center align-items-center text-muted">
            <i class="fa-brands fa-whatsapp mb-3" style="font-size: 4rem; opacity: 0.2;"></i>
            <h5>Select a conversation to start messaging</h5>
        </div>
        @endif
    </div>
</div>
@endsection
@push('scripts')
<script>
    function syncWaha() {
        if (window.Nexus && window.Nexus.showTaskLoader) {
            window.Nexus.showTaskLoader('Dispatched WAHA Contacts Sync...', 'Running background sync jobs...');
        }
        
        $.ajax({
            url: '{{ route("hub.waha.sync") }}',
            method: 'POST',
            data: JSON.stringify({ type: 'Contacts' }),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                if (window.Nexus && window.Nexus.notify) {
                    window.Nexus.notify('WhatsApp sync job dispatched successfully!', 'success');
                } else {
                    alert('WhatsApp sync job dispatched successfully!');
                }
                
                // Let's also dispatch message sync
                $.ajax({
                    url: '{{ route("hub.waha.sync") }}',
                    method: 'POST',
                    data: JSON.stringify({ type: 'Messages' }),
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        if (window.Nexus && window.Nexus.hideTaskLoader) {
                            window.Nexus.hideTaskLoader();
                        }
                    },
                    error: function() {
                        if (window.Nexus && window.Nexus.hideTaskLoader) {
                            window.Nexus.hideTaskLoader();
                        }
                    }
                });
            },
            error: function(err) {
                if (window.Nexus && window.Nexus.hideTaskLoader) {
                    window.Nexus.hideTaskLoader();
                }
                alert('Failed to dispatch WAHA sync: ' + (err.responseJSON?.message || 'Unknown error'));
            }
        });
    }

    function sendMessage() {
        const input = document.getElementById('messageInput');
        const text = input.value.trim();
        if (!text) return;

        const history = document.getElementById('messageHistory');
        const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        // Optimistic UI Update
        const bubble = `
            <div class="message-bubble outgoing animate-fade-in">
                ${text}
                <div class="text-end text-white-50 mt-1" style="font-size: 0.7rem;">${time} <i class="fa-solid fa-check text-muted ms-1"></i></div>
            </div>
        `;
        
        history.insertAdjacentHTML('beforeend', bubble);
        history.scrollTop = history.scrollHeight;
        input.value = '';

        @if($selectedContact)
        fetch('{{ route("hub.people-connect.message") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                contact_id: {{ $selectedContact->id }},
                content: text
            })
        })
        .then(response => response.json())
        .then(data => {
            if(!data.success) {
                alert('Error sending message');
            }
        })
        .catch(err => console.error(err));
        @endif
    }

    // Handle Enter key
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('messageInput');
        if(input) {
            input.addEventListener('keypress', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            // Scroll to bottom initially
            const history = document.getElementById('messageHistory');
            history.scrollTop = history.scrollHeight;
        }
    });
</script>
@endpush
