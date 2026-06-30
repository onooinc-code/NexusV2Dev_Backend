@extends('layouts.app')

@push('styles')
<style>
    .profile-header {
        background: linear-gradient(135deg, rgba(22,27,34,0.8) 0%, rgba(11,14,20,0.9) 100%);
        border-bottom: 1px solid var(--nexus-border);
    }
    .avatar-lg {
        width: 80px;
        height: 80px;
        font-size: 2.5rem;
    }
    .nav-tabs {
        border-bottom: 1px solid var(--nexus-border);
    }
    .nav-tabs .nav-link {
        color: var(--nexus-text-muted);
        border: none;
        border-bottom: 2px solid transparent;
        padding: 1rem 1.5rem;
        transition: all 0.3s ease;
    }
    .nav-tabs .nav-link:hover {
        border-color: transparent;
        color: var(--nexus-text);
        background: rgba(255,255,255,0.02);
    }
    .nav-tabs .nav-link.active {
        background: transparent;
        color: var(--nexus-primary);
        border-bottom: 2px solid var(--nexus-primary);
    }
    .tab-content {
        min-height: 400px;
    }
</style>
@endpush

@section('content')
<div class="row mb-3 animate-fade-in stagger-1">
    <div class="col-12">
        <a href="{{ route('hub.contacts') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Contacts
        </a>
    </div>
</div>

<!-- Profile Header Card -->
<div class="card hover-3d border-0 mb-4 animate-fade-in stagger-2">
    <div class="card-body profile-header rounded d-flex align-items-center p-4">
        <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-4 avatar-lg shadow">
            {{ strtoupper(substr($contact->first_name ?? $contact->name ?? 'U', 0, 1)) }}
        </div>
        <div>
            @php $isFav = $contact->isFavoritedBy(auth()->user()); @endphp
            <h2 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <span>{{ $contact->first_name ?? $contact->name }} {{ $contact->last_name ?? '' }}</span>
                <button class="btn-toggle-favorite border-0 bg-transparent p-0" 
                        data-id="{{ $contact->id }}" 
                        title="Toggle Favorite" 
                        onclick="toggleFavoriteProfile(this);">
                    <i class="{{ $isFav ? 'fa-solid' : 'fa-regular' }} fa-star" 
                       style="color: {{ $isFav ? '#eab308' : 'var(--text-muted)' }}; font-size: 1.3rem; cursor: pointer; transition: all 0.15s ease;"></i>
                </button>
            </h2>
            <div class="text-muted mb-2">
                <i class="fa-solid fa-briefcase me-1"></i> {{ $contact->company ?? 'Unknown Company' }} 
                <span class="mx-2">|</span> 
                <i class="fa-solid fa-envelope me-1"></i> {{ $contact->email ?? 'No Email' }}
                <span class="mx-2">|</span> 
                <i class="fa-solid fa-phone me-1"></i> {{ $contact->phone ?? 'No Phone' }}
            </div>
            <div class="d-flex align-items-center mt-2">
                <span class="badge bg-success bg-opacity-10 text-success border border-success me-2">
                    <i class="fa-solid fa-shield-check me-1"></i> Confidence: 98%
                </span>
                <span class="badge bg-info bg-opacity-10 text-info border border-info">
                    <i class="fa-brands fa-whatsapp me-1"></i> WAHA Connected
                </span>
            </div>
        </div>
        <div class="ms-auto text-end">
            <button class="btn btn-outline-primary me-2"><i class="fa-solid fa-pen me-1"></i> Edit</button>
            <div class="dropdown d-inline-block">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-bolt me-1"></i> Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#syncWahaModal"><i class="fa-brands fa-whatsapp text-success me-2"></i> Sync WAHA Messages</a></li>
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#importTxtModal"><i class="fa-solid fa-file-import text-info me-2"></i> Import TXT Messages</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="card hover-3d border-0 animate-fade-in stagger-3">
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline" type="button" role="tab">
                    <i class="fa-solid fa-chart-line me-2"></i> Timeline
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="messages-tab" data-bs-toggle="tab" data-bs-target="#messages" type="button" role="tab">
                    <i class="fa-solid fa-comments me-2"></i> Messages
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="memories-tab" data-bs-toggle="tab" data-bs-target="#memories" type="button" role="tab">
                    <i class="fa-solid fa-brain me-2"></i> Memories
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="intelligence-tab" data-bs-toggle="tab" data-bs-target="#intelligence" type="button" role="tab">
                    <i class="fa-solid fa-lightbulb me-2"></i> Intelligence
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rules-tab" data-bs-toggle="tab" data-bs-target="#rules" type="button" role="tab">
                    <i class="fa-solid fa-list-check me-2"></i> Rules & Preferences
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-4">
        <div class="tab-content" id="profileTabsContent">
            <!-- Timeline Tab -->
            <div class="tab-pane fade show active" id="timeline" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="text-light mb-4"><i class="fa-solid fa-history text-primary me-2"></i> Activity Timeline</h5>
                        
                        @forelse($auditEvents as $event)
                            <div class="position-relative border-start border-secondary ms-3 ps-4 pb-4">
                                <div class="position-absolute bg-primary rounded-circle" style="width: 12px; height: 12px; left: -6.5px; top: 0;"></div>
                                <div class="text-muted small mb-1">{{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}</div>
                                <div class="fw-bold text-light">{{ ucfirst(str_replace('_', ' ', $event->action)) }}</div>
                                @if($event->before_state || $event->after_state)
                                    <p class="text-muted small mt-1 mb-0 font-monospace">Details modified.</p>
                                @endif
                            </div>
                        @empty
                            <div class="text-muted small py-3">No timeline events found.</div>
                        @endforelse
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark border-secondary mb-3">
                            <div class="card-header border-secondary">
                                <h6 class="mb-0 text-light"><i class="fa-solid fa-chart-pie me-2"></i> Contact Stats</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Messages</span>
                                    <span class="text-light fw-bold">{{ number_format($stats['total_messages'] ?? 0) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Inbound / Outbound</span>
                                    <span class="text-light fw-bold">{{ number_format($stats['inbound'] ?? 0) }} / {{ number_format($stats['outbound'] ?? 0) }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Media Attachments</span>
                                    <span class="text-light fw-bold">{{ number_format($stats['has_media'] ?? 0) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-dark border-secondary">
                            <div class="card-header border-secondary">
                                <h6 class="mb-0 text-light"><i class="fa-solid fa-user-astronaut me-2 text-warning"></i> Personality</h6>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item bg-transparent border-secondary text-light">
                                        <div class="small text-muted mb-1">Communication Style</div>
                                        <div>Direct, Technical, Concise</div>
                                    </li>
                                    <li class="list-group-item bg-transparent border-secondary text-light">
                                        <div class="small text-muted mb-1">Tone</div>
                                        <div>Professional</div>
                                    </li>
                                    <li class="list-group-item bg-transparent border-secondary text-light">
                                        <div class="small text-muted mb-1">Key Traits</div>
                                        <div>Analytical, Results-oriented</div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages Tab -->
            <div class="tab-pane fade" id="messages" role="tabpanel">
                <h5 class="text-light mb-4"><i class="fa-solid fa-comments text-primary me-2"></i> Conversation History</h5>
                
                @if($messages->isEmpty())
                    <div class="alert alert-dark border-secondary text-center py-5">
                        <i class="fa-brands fa-whatsapp fa-2x text-muted mb-3"></i>
                        <p class="mb-0 text-muted">No messages found. Try syncing with WAHA.</p>
                    </div>
                @else
                    <div class="chat-container d-flex flex-column gap-3 mb-4" style="max-height: 600px; overflow-y: auto; padding-right: 10px;">
                        @foreach($messages->reverse() as $msg)
                            @if($msg->direction === 'outbound')
                                <!-- Outbound Message (Right) -->
                                <div class="d-flex justify-content-end">
                                    <div class="p-3 rounded text-light" style="background: var(--nexus-blue-dim); border: 1px solid var(--nexus-blue); max-width: 75%;">
                                        <div class="mb-1" style="white-space: pre-wrap; font-size: 0.9rem;">{{ $msg->content }}</div>
                                        <div class="text-end text-muted mt-2" style="font-size: 0.7rem;">
                                            {{ $msg->source_timestamp ? $msg->source_timestamp->format('M d, H:i') : $msg->created_at->format('M d, H:i') }}
                                            <i class="fa-solid fa-check-double ms-1" style="color: {{ $msg->status === 'read' ? 'var(--nexus-blue)' : 'inherit' }};"></i>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <!-- Inbound Message (Left) -->
                                <div class="d-flex justify-content-start">
                                    <div class="p-3 rounded text-light" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); max-width: 75%;">
                                        <div class="mb-1" style="white-space: pre-wrap; font-size: 0.9rem;">{{ $msg->content }}</div>
                                        <div class="text-start text-muted mt-2" style="font-size: 0.7rem;">
                                            {{ $msg->source_timestamp ? $msg->source_timestamp->format('M d, H:i') : $msg->created_at->format('M d, H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    
                    <div class="d-flex justify-content-center">
                        {{ $messages->links() }}
                    </div>
                @endif
            </div>

            <!-- Memories Tab -->
            <div class="tab-pane fade" id="memories" role="tabpanel">
                <h5 class="text-light mb-4"><i class="fa-solid fa-brain text-primary me-2"></i> Extracted Memories</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-dark border border-secondary rounded">
                            <span class="badge bg-secondary mb-2">Fact</span>
                            <p class="mb-1 text-light">Prefers meetings in the morning (9 AM - 11 AM).</p>
                            <small class="text-muted">Extracted 3 days ago</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-dark border border-secondary rounded">
                            <span class="badge bg-secondary mb-2">Preference</span>
                            <p class="mb-1 text-light">Interested in AI Automation products.</p>
                            <small class="text-muted">Extracted 1 week ago</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Intelligence Tab -->
            <div class="tab-pane fade" id="intelligence" role="tabpanel">
                <h5 class="text-light mb-4"><i class="fa-solid fa-lightbulb text-warning me-2"></i> AI Analysis & Insights</h5>
                <div class="alert alert-dark border-secondary">
                    <h6 class="text-light"><i class="fa-solid fa-robot text-primary me-2"></i> Nexus Core Summary</h6>
                    <p class="text-muted mb-0">This contact shows high engagement in technical topics. They usually reply within 2 hours. Recommended communication style: Direct and Technical.</p>
                </div>
            </div>

            <!-- Rules Tab -->
            <div class="tab-pane fade" id="rules" role="tabpanel">
                <h5 class="text-light mb-4"><i class="fa-solid fa-list-check text-primary me-2"></i> Response Rules</h5>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="autoPilotSwitch" checked>
                    <label class="form-check-label text-light" for="autoPilotSwitch">Enable Autopilot Responses</label>
                </div>
                <div class="p-3 bg-dark border border-secondary rounded">
                    <h6 class="text-light mb-2">Custom System Prompt</h6>
                    <textarea class="form-control" rows="3">When replying to this contact, always maintain a highly professional and technical tone. Reference previous AI topics if applicable.</textarea>
                    <button class="btn btn-sm btn-primary mt-3">Save Rule</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sync WAHA Modal -->
<div class="modal fade" id="syncWahaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-secondary">
            <div class="modal-header border-secondary bg-dark">
                <h5 class="modal-title text-light"><i class="fa-brands fa-whatsapp text-success me-2"></i> Sync WAHA Messages</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-dark text-light">
                <p class="text-muted small mb-4">This process fetches all historical messages for this contact from WAHA in chunks. It may take some time depending on the message volume.</p>
                
                <div class="progress bg-black mb-2" style="height: 10px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="sync-waha-progress-bar" style="width: 0%;"></div>
                </div>
                <div class="d-flex justify-content-between text-muted small font-monospace">
                    <span id="sync-waha-status">Ready</span>
                    <span id="sync-waha-count">0 / 0</span>
                </div>
            </div>
            <div class="modal-footer border-secondary bg-dark">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-outline-warning" id="btn-pause-waha"><i class="fa-solid fa-pause"></i> Pause</button>
                <button type="button" class="btn btn-sm btn-success" id="btn-start-waha" onclick="startWahaSync({{ $contact->id }})"><i class="fa-solid fa-play me-1"></i> Start Sync</button>
            </div>
        </div>
    </div>
</div>

<!-- Import TXT Modal -->
<div class="modal fade" id="importTxtModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-secondary">
            <div class="modal-header border-secondary bg-dark">
                <h5 class="modal-title text-light"><i class="fa-solid fa-file-import text-info me-2"></i> Import WhatsApp TXT</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-dark text-light">
                <p class="text-muted small mb-4">Upload an exported WhatsApp chat `.txt` file. We will parse it and merge the messages into the timeline, skipping duplicates.</p>
                
                <div class="mb-3">
                    <input class="form-control bg-black text-light border-secondary" type="file" id="txtUploadFile" accept=".txt">
                </div>

                <div class="progress bg-black mb-2" style="height: 10px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" id="import-txt-progress-bar" style="width: 0%;"></div>
                </div>
                <div class="d-flex justify-content-between text-muted small font-monospace">
                    <span id="import-txt-status">Ready</span>
                    <span id="import-txt-count">0 / 0</span>
                </div>
            </div>
            <div class="modal-footer border-secondary bg-dark">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-info" id="btn-start-import"><i class="fa-solid fa-upload me-1"></i> Upload & Parse</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // If msg_page is in the URL, activate the messages tab automatically
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('msg_page')) {
            const msgTab = new bootstrap.Tab(document.querySelector('#messages-tab'));
            msgTab.show();
        }
    });
</script>
@endpush

@push('scripts')
<script>
    window.toggleFavoriteProfile = function(btn) {
        const contactId = $(btn).data('id');
        const starIcon = $(btn).find('i');
        
        starIcon.removeClass('fa-solid fa-regular').addClass('fa-solid fa-spinner fa-spin').css('color', 'var(--nexus-blue)');

        $.ajax({
            url: `/hub/contacts/${contactId}/toggle-favorite`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                if (res.success) {
                    starIcon.removeClass('fa-spinner fa-spin');
                    if (res.is_favorite) {
                        starIcon.removeClass('fa-regular').addClass('fa-solid').css('color', '#eab308');
                    } else {
                        starIcon.removeClass('fa-solid').addClass('fa-regular').css('color', 'var(--text-muted)');
                    }
                    Nexus.notify(res.message, 'success');
                }
            },
            error: function() {
                starIcon.removeClass('fa-spinner fa-spin');
                // Restore state
                starIcon.addClass('fa-solid').css('color', '#eab308');
                Nexus.notify('Failed to toggle favorite.', 'error');
            }
        });
    };
    window.startWahaSync = function(contactId) {
        document.getElementById('btn-start-waha').disabled = true;
        document.getElementById('sync-waha-status').innerText = 'Queuing...';
        
        fetch(`/api/v1/settings/waha-manage/sync/contact/${contactId}`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('sync-waha-status').innerText = 'Syncing...';
            // Start listening to echo if available
            Nexus.notify('Sync job started. Progress updates will appear here.', 'success');
        })
        .catch(error => {
            document.getElementById('btn-start-waha').disabled = false;
            document.getElementById('sync-waha-status').innerText = 'Failed';
            Nexus.notify('Failed to start sync', 'error');
        });
    };

    if(window.Echo) {
        window.Echo.channel('system-events')
            .listen('JobProgressUpdated', (e) => {
                const pb = document.getElementById('sync-waha-progress-bar');
                const st = document.getElementById('sync-waha-status');
                const ct = document.getElementById('sync-waha-count');
                
                if (pb && st && ct) {
                    pb.style.width = e.progress + '%';
                    st.innerText = e.status === 'completed' ? 'Done' : e.status;
                    ct.innerText = `${e.processedItems} / ${e.totalItems}`;
                    
                    if (e.status === 'completed') {
                        document.getElementById('btn-start-waha').disabled = false;
                        Nexus.notify('WAHA Sync Complete!', 'success');
                        setTimeout(() => location.reload(), 2000);
                    }
                }
            });
    }
</script>
@endpush
