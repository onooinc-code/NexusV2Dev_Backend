@extends('layouts.app')

@push('styles')
    <!-- Drawflow CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/jerosoler/Drawflow/dist/drawflow.min.css">
    <style>
        #drawflow {
            position: relative;
            width: 100%;
            height: 600px;
            background: var(--nexus-panel);
            background-size: 25px 25px;
            background-image: linear-gradient(to right, rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.05) 1px, transparent 1px);
            border: 1px solid var(--nexus-border);
            border-radius: 8px;
        }
        /* Custom Drawflow Theme matching Nexus dark aesthetic */
        .drawflow .drawflow-node {
            background: #1c2128;
            border: 1px solid var(--nexus-border);
            color: var(--nexus-text);
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        .drawflow .drawflow-node.selected {
            background: #22272e;
            border: 2px solid var(--nexus-primary);
        }
        .drawflow .drawflow-node .inputs .input {
            background: #3fb950;
            border: 2px solid #1c2128;
        }
        .drawflow .drawflow-node .outputs .output {
            background: var(--nexus-primary);
            border: 2px solid #1c2128;
        }
        .drawflow .connection .main-path {
            stroke: #8b949e;
            stroke-width: 3px;
        }
    </style>
@endpush

@section('content')
<div class="row mb-4 align-items-center animate-fade-in stagger-1">
    <div class="col-md-6">
        <h2 class="fw-bold mb-0">WorkflowsHub</h2>
        <p class="text-muted">Visual Workflow Builder & Execution Pipeline.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <button class="btn btn-outline-success me-2" id="btn-run-workflow">
            <i class="fa-solid fa-play me-1"></i> Run Simulator
        </button>
        <button class="btn btn-primary" id="btn-save-workflow">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Workflow
        </button>
    </div>
</div>

<div class="row g-3 animate-fade-in stagger-2">
    <!-- Workflows List Sidebar -->
    <div class="col-md-3">
        <div class="card h-100 bg-dark border-secondary">
            <div class="card-header border-secondary text-light fw-bold bg-transparent">
                <i class="fa-solid fa-list text-primary me-2"></i> Workflows
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush bg-transparent" id="workflows-list">
                    @foreach($workflows as $index => $wf)
                    <button class="list-group-item list-group-item-action bg-dark text-light border-secondary {{ $index === 0 ? 'active' : '' }}" 
                            data-id="{{ $wf->id }}" 
                            data-name="{{ $wf->name }}"
                            data-steps='{!! json_encode($wf->steps ?? []) !!}'
                            style="{{ $index === 0 ? 'border-left: 3px solid var(--nexus-primary) !important;' : '' }}">
                        <div class="fw-bold">{{ $wf->name }}</div>
                        @if($wf->is_active)
                            <small class="text-success"><i class="fa-solid fa-check-circle"></i> Active</small>
                        @else
                            <small class="text-muted"><i class="fa-solid fa-pause-circle"></i> Inactive</small>
                        @endif
                    </button>
                    @endforeach
                </div>
            </div>
            <div class="card-footer border-secondary bg-transparent">
                <button class="btn btn-outline-primary btn-sm w-100"><i class="fa-solid fa-plus me-1"></i> New Workflow</button>
            </div>
        </div>
    </div>
    
    <!-- Main Canvas and Logs -->
    <div class="col-md-9 d-flex flex-column gap-3">
        <!-- Node Canvas -->
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary bg-transparent d-flex justify-content-between align-items-center">
                <span class="fw-bold text-light"><i class="fa-solid fa-diagram-project text-info me-2"></i> Node Canvas</span>
                <span class="badge bg-secondary">Draft</span>
            </div>
            <div class="card-body p-0">
                <div id="drawflow" ondrop="drop(event)" ondragover="allowDrop(event)"></div>
            </div>
        </div>

        <!-- Realtime Logs Console -->
        <div class="card bg-dark border-secondary" style="height: 250px;">
            <div class="card-header border-secondary bg-transparent d-flex justify-content-between align-items-center py-2">
                <span class="fw-bold text-light small"><i class="fa-solid fa-terminal text-success me-2"></i> Execution Logs</span>
                <button class="btn btn-sm btn-link text-muted p-0"><i class="fa-solid fa-trash"></i></button>
            </div>
            <div class="card-body bg-black overflow-auto" style="font-family: monospace; font-size: 0.85rem;" id="workflow-logs">
                <div class="text-muted">> Waiting for execution...</div>
                <div class="text-primary mt-2">> [00:00:01] Workflow 'Lead Generation Sync' triggered manually.</div>
                <div class="text-success">> [00:00:02] Node 1 (Trigger): Success</div>
                <div class="text-warning">> [00:00:03] Node 2 (Action): Awaiting Human Approval...</div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Gate Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="fa-solid fa-shield-halved text-warning me-2"></i> Human Approval Required</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>The workflow <strong>Lead Generation Sync</strong> is paused at node <strong>Send Bulk Emails</strong>.</p>
                <div class="alert alert-warning bg-warning bg-opacity-10 border-warning text-light">
                    <i class="fa-solid fa-triangle-exclamation text-warning me-2"></i> Please review the generated emails before allowing the system to send them.
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i> Reject</button>
                <button type="button" class="btn btn-success"><i class="fa-solid fa-check me-1"></i> Approve & Continue</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/gh/jerosoler/Drawflow/dist/drawflow.min.js"></script>
<script>
    var id = document.getElementById("drawflow");
    const editor = new Drawflow(id);
    editor.reroute = true;
    editor.start();

    let currentWorkflowId = null;
    let echoChannel = null;

    function logToTerminal(message, type = 'info') {
        const logsContainer = document.getElementById('workflow-logs');
        const timestamp = new Date().toLocaleTimeString();
        let colorClass = 'text-light';
        if (type === 'success') colorClass = 'text-success';
        if (type === 'error') colorClass = 'text-danger';
        if (type === 'warning') colorClass = 'text-warning';
        if (type === 'info') colorClass = 'text-info';

        const line = `<div class="${colorClass} mt-1">> [${timestamp}] ${message}</div>`;
        logsContainer.innerHTML += line;
        logsContainer.scrollTop = logsContainer.scrollHeight;
    }

    function loadWorkflow(workflowBtn) {
        const id = $(workflowBtn).data('id');
        const name = $(workflowBtn).data('name');
        let steps = $(workflowBtn).data('steps');

        // Clear previous channel subscription
        if (echoChannel && currentWorkflowId) {
            window.Echo.leave(`workflow.${currentWorkflowId}`);
            echoChannel = null;
        }

        currentWorkflowId = id;

        // Visual toggle in sidebar
        $('#workflows-list button').removeClass('active').css('border-left', '');
        $(workflowBtn).addClass('active').css('border-left', '3px solid var(--nexus-primary) !important');

        // Clear and draw canvas
        editor.clear();
        logToTerminal(`Loaded workflow: '${name}'`);

        if (!steps || steps.length === 0) {
            logToTerminal('No steps defined for this workflow.', 'warning');
            return;
        }

        // Draw nodes sequentially
        steps.forEach((step, idx) => {
            const nodeX = 150 + idx * 250;
            const nodeY = 150;
            const nodeName = step.name || `Step ${idx + 1}`;
            const nodeType = step.action || step.type || 'action';
            const nodeId = idx + 1;

            let icon = 'fa-code';
            let color = 'text-success';
            if (nodeType === 'agent') { icon = 'fa-robot'; color = 'text-warning'; }
            if (nodeType === 'condition' || nodeType === 'decision') { icon = 'fa-code-branch'; color = 'text-info'; }
            if (nodeType === 'trigger') { icon = 'fa-bolt'; color = 'text-primary'; }
            if (nodeType === 'log') { icon = 'fa-terminal'; color = 'text-secondary'; }

            const html = `
                <div class="p-2" data-step-id="${step.id || step.name}">
                    <div class="fw-bold mb-1"><i class="fa-solid ${icon} ${color}"></i> ${nodeName}</div>
                    <small class="text-muted" style="font-size:0.75rem;">Type: ${nodeType}</small>
                </div>
            `;

            const inputs = idx === 0 ? 0 : 1;
            const outputs = idx === steps.length - 1 ? 0 : 1;

            editor.addNode(step.id || step.name || `node_${nodeId}`, inputs, outputs, nodeX, nodeY, nodeType, {}, html);

            if (idx > 0) {
                editor.addConnection(idx, idx + 1, 'output_1', 'input_1');
            }
        });

        // Connect to Echo for realtime updates
        if (window.Echo) {
            echoChannel = window.Echo.private(`workflow.${currentWorkflowId}`);
            echoChannel.listen('.workflow.step_completed', (e) => {
                const nodeDiv = $(`.drawflow-node [data-step-id="${e.step_id}"]`);
                if (nodeDiv.length) {
                    const nodeEl = nodeDiv.closest('.drawflow-node');
                    nodeEl.css({
                        'border': '',
                        'box-shadow': '',
                        'animation': ''
                    });

                    if (e.status === 'running') {
                        nodeEl.css({
                            'border': '2px solid var(--nexus-teal)',
                            'box-shadow': '0 0 12px var(--nexus-teal)',
                            'animation': 'breathing-glow 1.5s infinite'
                        });
                        logToTerminal(`Node '${e.step_title}' is running...`, 'info');
                    } else if (e.status === 'completed' || e.status === 'success') {
                        nodeEl.css({
                            'border': '2px solid #3fb950',
                            'box-shadow': '0 0 10px rgba(63, 185, 80, 0.4)'
                        });
                        logToTerminal(`Node '${e.step_title}' completed successfully in ${e.duration_ms}ms`, 'success');
                    } else if (e.status === 'failed') {
                        nodeEl.css({
                            'border': '2px solid var(--error)',
                            'box-shadow': '0 0 10px rgba(239, 68, 68, 0.4)'
                        });
                        logToTerminal(`Node '${e.step_title}' failed: ${e.error}`, 'error');
                    } else if (e.status === 'paused') {
                        nodeEl.css({
                            'border': '2px solid var(--amber)',
                            'box-shadow': '0 0 10px rgba(245, 158, 11, 0.4)',
                            'animation': 'blink 1s infinite'
                        });
                        logToTerminal(`Node '${e.step_title}' is paused. Human approval required.`, 'warning');
                        const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
                        modal.show();
                    }
                }
            });
            logToTerminal('Subscribed to workflow events channel.', 'success');
        }
    }

    // Initialize the first workflow on page load
    $(document).ready(function() {
        const firstWf = $('#workflows-list button').first();
        if (firstWf.length) {
            loadWorkflow(firstWf);
        }

        $('#workflows-list button').click(function() {
            loadWorkflow(this);
        });
    });

    // Execute Workflow Trigger
    $('#btn-run-workflow').off('click').click(function() {
        if (!currentWorkflowId) {
            alert('Please select a workflow first.');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Triggering...');
        
        logToTerminal('Triggering workflow execution...', 'info');

        $.ajax({
            url: `/hub/workflows/${currentWorkflowId}/execute`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(resp) {
                logToTerminal(`Workflow execution queued successfully. Execution ID: ${resp.execution_id}`, 'success');
                btn.prop('disabled', false).html('<i class="fa-solid fa-play me-1"></i> Run Simulator');
            },
            error: function(xhr) {
                const err = xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error';
                logToTerminal(`Failed to execute workflow: ${err}`, 'error');
                btn.prop('disabled', false).html('<i class="fa-solid fa-play me-1"></i> Run Simulator');
            }
        });
    });

    // Save Mock
    $('#btn-save-workflow').click(function() {
        const data = editor.export();
        console.log("Exported Workflow JSON:", data);
        alert('Workflow JSON saved to console. (Mock implementation)');
    });
</script>
@endpush
