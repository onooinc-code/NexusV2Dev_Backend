@extends('layouts.app')

@push('styles')
<style>
    .waha-stat-card {
        background: rgba(22, 27, 34, 0.5);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--nexus-border);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: transform 0.2s;
    }
    .waha-stat-card:hover {
        transform: translateY(-2px);
    }
    .process-card {
        background: rgba(11, 14, 20, 0.4);
        border: 1px solid var(--nexus-border);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
    }
    .terminal-box {
        background: #000;
        border: 1px solid var(--nexus-border);
        border-radius: 8px;
        height: 200px;
        overflow-y: auto;
        padding: 10px;
        font-family: monospace;
        font-size: 0.8rem;
        color: #4ade80;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 text-light mb-1"><i class="fa-solid fa-server text-primary me-2"></i> WAHA Engine Orchestration</h2>
        <p class="text-muted small mb-0">Manage WhatsApp API synchronization, message ingestion, and AI attribute extraction.</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm" onclick="refreshWaha()">
            <i class="fa-solid fa-arrows-rotate me-1"></i> Refresh
        </button>
    </div>
</div>

<div class="row mb-4 animate-fade-in stagger-1" id="waha-stats-container">
    <div class="col-md-4">
        <div class="waha-stat-card">
            <i class="fa-solid fa-address-book text-primary mb-2" style="font-size: 2rem;"></i>
            <h3 class="text-light fw-bold" id="stat-contacts">0</h3>
            <p class="text-muted text-uppercase mb-0" style="font-size: 0.75rem; letter-spacing: 1px;">
                <span class="text-success"><span id="stat-synced-contacts">0</span> Synced</span> • 
                <span class="text-warning"><span id="stat-unsynced-contacts">0</span> Missing</span>
            </p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="waha-stat-card">
            <i class="fa-solid fa-list text-secondary mb-2" style="font-size: 2rem;"></i>
            <h3 class="text-light fw-bold" id="stat-messages">0</h3>
            <p class="text-muted text-uppercase mb-0" style="font-size: 0.75rem; letter-spacing: 1px;">Ingested Messages</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="waha-stat-card" style="border-color: rgba(249, 115, 22, 0.3);">
            <i class="fa-solid fa-brain text-warning mb-2" style="font-size: 2rem;"></i>
            <h3 class="text-light fw-bold" id="stat-jobs">0</h3>
            <p class="text-muted text-uppercase mb-0" style="font-size: 0.75rem; letter-spacing: 1px;">Active Jobs</p>
        </div>
    </div>
</div>

<div class="row animate-fade-in stagger-2">
    <div class="col-md-6 mb-4">
        <div class="card bg-transparent border-secondary h-100">
            <div class="card-header border-secondary d-flex align-items-center">
                <i class="fa-solid fa-play text-primary me-2"></i> <h6 class="mb-0 text-light">Active Sync Processes</h6>
            </div>
            <div class="card-body p-4" style="background: rgba(0, 122, 255, 0.05);">
                
                <div id="active-processes-container">
                    <div class="text-muted small">Loading processes...</div>
                </div>

                <div class="d-flex gap-2 mt-4 pt-3 border-top border-secondary border-opacity-50">
                    <button class="btn btn-sm btn-outline-primary" onclick="startProcess('sync_contacts')">Sync Contacts</button>
                    <button class="btn btn-sm btn-outline-primary" onclick="startProcess('sync_messages')">Sync Messages</button>
                    <button class="btn btn-sm btn-outline-warning" onclick="openAnalyzeModal()"><i class="fa-solid fa-brain me-1"></i> Batch Analyze AI</button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card bg-transparent border-secondary h-100">
            <div class="card-header border-secondary d-flex align-items-center bg-black">
                <i class="fa-solid fa-terminal text-muted me-2"></i> <span class="text-muted font-monospace" style="font-size: 0.8rem;">WAHA Subsystem Output</span>
            </div>
            <div class="card-body p-0">
                <div class="terminal-box" id="wahaTerminal">
                    <div>[10:45:01] Listening for events...</div>
                    <div>[10:45:05] Starting SYNC_MESSAGES process...</div>
                    <div>[10:45:06] Fetching batch 1/10 from WAHA API...</div>
                    <div>[10:45:08] 100 messages ingested successfully.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card bg-transparent border-secondary animate-fade-in stagger-3">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-light">Synced Contacts</h6>
        <div style="width: 250px;">
            <input type="text" class="form-control form-control-sm bg-dark text-light border-secondary" id="searchContacts" placeholder="Search by name or ID...">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>WAHA ID</th>
                        <th>Messages</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="contacts-table-body">
                    <tr>
                        <td colspan="4" class="text-center text-muted small py-4">Loading contacts...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer border-secondary d-flex justify-content-between align-items-center">
        <span class="text-muted small" id="pagination-info">Showing 0 of 0</span>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-secondary" id="btn-prev-page" onclick="changePage(-1)">Prev</button>
            <button class="btn btn-sm btn-outline-secondary" id="btn-next-page" onclick="changePage(1)">Next</button>
        </div>
    </div>
</div>

<!-- Analyze Modal -->
<div class="modal fade" id="analyzeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light"><i class="fa-solid fa-brain text-primary me-2"></i> Analyze Contact</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning py-2 px-3 d-flex align-items-center mb-4" style="background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.2); color: #fcd34d; font-size: 0.8rem;">
                    <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
                    <div><strong>Token Warning:</strong> Analyzing large volumes of messages will consume significant LLM tokens.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small">Agent (Strategy ID)</label>
                    <input type="text" class="form-control" id="analyze-agent-id" placeholder="e.g. 1">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Model Override</label>
                    <input type="text" class="form-control" id="analyze-model-override" placeholder="gpt-4o">
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small">Message Limit (Recent Context)</label>
                    <input type="number" class="form-control" id="analyze-message-limit" value="50">
                    <input type="hidden" id="analyze-contact-id" value="">
                </div>

                <h6 class="text-muted small mb-2">Extraction Targets</h6>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="analyze-pref" checked>
                    <label class="form-check-label text-light" style="font-size: 0.85rem;">Contact Preferences</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="analyze-pers" checked>
                    <label class="form-check-label text-light" style="font-size: 0.85rem;">Personality Attributes</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="analyze-topic" checked>
                    <label class="form-check-label text-light" style="font-size: 0.85rem;">Discussion Topics</label>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="queueAnalysis()" data-bs-dismiss="modal"><i class="fa-solid fa-brain me-1"></i> Queue Analysis</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function refreshWaha() {
        Nexus.showTaskLoader('Refreshing WAHA Stats...');
        fetchStats();
        fetchContacts();
        setTimeout(() => {
            Nexus.hideTaskLoader();
        }, 1000);
    }

    async function fetchStats() {
        try {
            const res = await fetch('/api/v1/settings/waha-manage/status');
            const data = await res.json();
            
            document.getElementById('stat-contacts').innerText = data.stats.total_waha_contacts.toLocaleString();
            document.getElementById('stat-synced-contacts').innerText = data.stats.synced_contacts.toLocaleString();
            document.getElementById('stat-unsynced-contacts').innerText = data.stats.unsynced_contacts.toLocaleString();
            document.getElementById('stat-messages').innerText = data.stats.total_messages.toLocaleString();
            document.getElementById('stat-jobs').innerText = data.active_processes.length;

            const container = document.getElementById('active-processes-container');
            if (data.active_processes.length === 0) {
                container.innerHTML = '<div class="text-muted small">No active sync processes.</div>';
            } else {
                container.innerHTML = data.active_processes.map(p => `
                    <div class="process-card" id="process-${p.id}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="text-light font-monospace text-uppercase" style="font-size: 0.85rem;">${p.type}</span>
                                <span class="badge ${p.status === 'running' ? 'bg-success' : 'bg-secondary'} bg-opacity-10 ${p.status === 'running' ? 'text-success' : 'text-secondary'} ms-2">${p.status.toUpperCase()}</span>
                            </div>
                            ${p.status === 'running' ? `<button class="btn btn-sm btn-outline-secondary border-0" onclick="pauseProcess(${p.id})"><i class="fa-solid fa-pause"></i> Pause</button>` : ''}
                        </div>
                        <div class="progress bg-dark mb-1" style="height: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="progress-bar-${p.id}" style="width: 100%;"></div>
                        </div>
                        <div class="text-muted text-end" id="progress-text-${p.id}" style="font-size: 0.7rem;">In Progress...</div>
                    </div>
                `).join('');
            }
        } catch (e) {
            console.error('Error fetching stats:', e);
        }
    }

    let allContacts = [];
    let currentPage = 1;
    const itemsPerPage = 10;

    async function fetchContacts() {
        try {
            const res = await fetch('/api/v1/settings/waha-manage/contacts');
            const data = await res.json();
            allContacts = data.data;
            renderContacts();
        } catch (e) {
            console.error('Error fetching contacts:', e);
        }
    }

    function renderContacts() {
        const tbody = document.getElementById('contacts-table-body');
        const searchInput = document.getElementById('searchContacts');
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        
        const filtered = allContacts.filter(c => 
            (c.name && c.name.toLowerCase().includes(searchTerm)) || 
            (c.waha_contact_id && c.waha_contact_id.toLowerCase().includes(searchTerm))
        );

        const totalPages = Math.ceil(filtered.length / itemsPerPage);
        if (currentPage > totalPages) currentPage = totalPages || 1;

        const startIdx = (currentPage - 1) * itemsPerPage;
        const paged = filtered.slice(startIdx, startIdx + itemsPerPage);

        if (paged.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted small py-4">No contacts found.</td></tr>';
            document.getElementById('pagination-info').innerText = `Showing 0 of 0`;
            return;
        }

        tbody.innerHTML = paged.map(c => `
            <tr>
                <td>${c.name || 'Unknown'}</td>
                <td class="font-monospace text-muted" style="font-size: 0.8rem;">${c.waha_contact_id}</td>
                <td><span class="badge bg-secondary bg-opacity-25 text-light">${c.messages_count} MSGS</span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-info me-1" onclick="syncContactMsgs(${c.id})"><i class="fa-solid fa-cloud-arrow-down"></i> Sync MSGS</button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="openAnalyzeModal(${c.id})"><i class="fa-solid fa-brain me-1"></i> Analyze</button>
                </td>
            </tr>
        `).join('');

        document.getElementById('pagination-info').innerText = `Showing ${startIdx + 1}-${startIdx + paged.length} of ${filtered.length}`;
        const prevBtn = document.getElementById('btn-prev-page');
        const nextBtn = document.getElementById('btn-next-page');
        if (prevBtn) prevBtn.disabled = currentPage === 1;
        if (nextBtn) nextBtn.disabled = currentPage === totalPages;
    }

    function changePage(delta) {
        currentPage += delta;
        renderContacts();
    }

    function startProcess(type) {
        Nexus.showTaskLoader(`Starting ${type}...`);
        
        fetch('/api/v1/settings/waha-manage/sync/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ type: type })
        })
        .then(response => response.json())
        .then(data => {
            Nexus.hideTaskLoader();
            logTerminal(`Process ${type} started.`);
            fetchStats();
        })
        .catch(error => {
            Nexus.hideTaskLoader();
            logTerminal(`Error starting process: ${error.message}`, 'red');
        });
    }

    function syncContactMsgs(id) {
        Nexus.showTaskLoader(`Queuing Single Contact Sync...`);
        fetch(`/api/v1/settings/waha-manage/sync/contact/${id}`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(response => response.json())
        .then(data => {
            Nexus.hideTaskLoader();
            logTerminal(`Process single contact sync started.`, '#fbbf24');
            fetchStats();
        });
    }

    function pauseProcess(id) {
        fetch(`/api/v1/settings/waha-manage/sync/${id}/pause`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(() => fetchStats());
    }

    function openAnalyzeModal(contactId = null) {
        if(contactId) {
            document.getElementById('analyze-contact-id').value = contactId;
        } else {
            document.getElementById('analyze-contact-id').value = '';
        }
        var analyzeModal = new bootstrap.Modal(document.getElementById('analyzeModal'));
        analyzeModal.show();
    }

    function queueAnalysis() {
        Nexus.showTaskLoader('Queuing AI Analysis Job...');
        
        const payload = {
            agent_id: document.getElementById('analyze-agent-id').value || 1,
            model_id: document.getElementById('analyze-model-override').value || 'gpt-4o',
            message_limit: parseInt(document.getElementById('analyze-message-limit').value) || 50,
            extract_preferences: document.getElementById('analyze-pref').checked,
            extract_personality: document.getElementById('analyze-pers').checked,
            extract_topics: document.getElementById('analyze-topic').checked
        };
        
        const contactId = document.getElementById('analyze-contact-id').value;
        if(contactId) payload.contact_ids = [contactId];

        fetch('/api/v1/settings/waha-manage/analyze/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            Nexus.hideTaskLoader();
            logTerminal('Job Queued: Analyze Contacts', '#fbbf24');
            fetchStats();
        });
    }

    function logTerminal(message, color = '#4ade80') {
        const term = document.getElementById('wahaTerminal');
        const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
        term.innerHTML += `<div style="color: ${color};">[${time}] ${message}</div>`;
        term.scrollTop = term.scrollHeight;
    }

    document.addEventListener('DOMContentLoaded', () => {
        fetchStats();
        fetchContacts();

        document.getElementById('searchContacts')?.addEventListener('input', () => {
            currentPage = 1; // reset to first page on search
            renderContacts();
        });

        document.getElementById('wahaTerminal').innerHTML = ''; // Clear initial terminal
        logTerminal('Listening for events...');

        if(window.Echo) {
            window.Echo.channel('system-events')
                .listen('JobProgressUpdated', (e) => {
                    let color = '#4ade80'; // Success green
                    if(e.status === 'running') color = '#fbbf24'; // Warning yellow
                    if(e.status === 'failed') color = '#ef4444'; // Red
                    
                    logTerminal(`[${e.type.toUpperCase()}] ${e.message} (${e.progress}%)`, color);
                    
                    // Update progress bar if it exists
                    const pb = document.getElementById(`progress-bar-${e.jobId}`);
                    const pt = document.getElementById(`progress-text-${e.jobId}`);
                    if (pb && pt) {
                        pb.style.width = e.progress + '%';
                        pt.innerText = `${e.processedItems} / ${e.totalItems} processed • ${e.progress}%`;
                        if(e.status === 'completed' || e.status === 'failed') {
                            setTimeout(fetchStats, 1000);
                        }
                    }
                });
        }
        
        // Smart Polling fallback if processes are active
        setInterval(() => {
            if (document.getElementById('stat-jobs').innerText !== "0") {
                fetchStats();
                fetchContacts();
            }
        }, 5000);
    });
</script>
@endpush
