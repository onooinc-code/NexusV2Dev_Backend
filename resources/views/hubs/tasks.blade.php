@extends('layouts.app')
@section('page_title', 'Task Objectives')

@push('styles')
<style>
.kanban-col {
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    padding: 16px;
    min-height: 500px;
    display: flex;
    flex-direction: column;
}
.kanban-col-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--glass-border);
    margin-bottom: 14px;
}
.kanban-col-title {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-weight: 600;
}
.kanban-col-count {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.65rem;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 600;
}

.task-card {
    background: linear-gradient(135deg, rgba(15,23,42,0.7) 0%, rgba(30,41,59,0.4) 100%);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 10px;
    transition: all 0.2s var(--ease-smooth);
    position: relative;
    overflow: hidden;
    cursor: pointer;
}
.task-card:hover {
    border-color: var(--nexus-blue-glow);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.4);
}
.task-card.priority-critical { border-left: 3px solid hsl(0,84%,60%); }
.task-card.priority-high     { border-left: 3px solid hsl(0,84%,60%); }
.task-card.priority-medium   { border-left: 3px solid var(--amber); }
.task-card.priority-low      { border-left: 3px solid var(--nexus-teal); }
.task-card.priority-none     { border-left: 3px solid var(--glass-border); }

.task-progress {
    position: absolute;
    bottom: 0; left: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--nexus-blue), var(--nexus-teal));
    border-radius: 0 0 0 10px;
    transition: width 0.5s ease;
}

.priority-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 0.6rem;
    font-family: 'JetBrains Mono', monospace;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}
.priority-badge.critical, .priority-badge.high   { background: var(--error-dim); border: 1px solid hsla(0,84%,60%,0.3); color: hsl(0,84%,70%); }
.priority-badge.medium  { background: var(--amber-dim); border: 1px solid hsla(38,92%,50%,0.3); color: hsl(38,92%,65%); }
.priority-badge.low     { background: var(--nexus-teal-dim); border: 1px solid hsla(174,90%,41%,0.3); color: hsl(174,90%,60%); }

/* Exec terminal modal */
.exec-log-terminal {
    background: hsl(224,71%,2%);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    padding: 16px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.72rem;
    line-height: 1.8;
    height: 300px;
    overflow-y: auto;
}
.exec-log-terminal::-webkit-scrollbar { width: 3px; }
.exec-log-terminal::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.06); }
</style>
@endpush

@section('content')
<div class="d-flex flex-column gap-4 animate-in">

    {{-- ═══ HEADER ═══ --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 stagger-1" style="opacity: 0;">
        <div class="d-flex align-items-center gap-3">
            <div style="width: 42px; height: 42px; background: var(--nexus-teal-dim); border: 1px solid hsla(174,90%,41%,0.3); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-list-check" style="color: var(--nexus-teal); font-size: 1.1rem;"></i>
            </div>
            <div>
                <h1 class="mb-0" style="font-size: 1.4rem; font-weight: 700; letter-spacing: -0.02em;">Task Objectives</h1>
                <p class="text-muted mb-0" style="font-size: 0.8rem;">AI agent task management & execution monitoring</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal" style="font-size: 0.78rem; padding: 6px 16px; border-radius: 8px;">
                <i class="fa-solid fa-plus me-1"></i> New Task
            </button>
        </div>
    </div>

    {{-- ═══ SUMMARY CHIPS ═══ --}}
    <div class="d-flex flex-wrap gap-2 stagger-2" style="opacity: 0;">
        <span class="stats-chip"><i class="fa-solid fa-hourglass-half" style="color: var(--text-muted);"></i> {{ $todo->count() }} Pending</span>
        <span class="stats-chip"><i class="fa-solid fa-spinner fa-spin" style="color: var(--nexus-teal);"></i> {{ $inProgress->count() }} Running</span>
        <span class="stats-chip"><i class="fa-solid fa-circle-check" style="color: var(--success-bright);"></i> {{ $completed->count() }} Done</span>
        <span class="stats-chip"><i class="fa-solid fa-circle-xmark" style="color: var(--error);"></i> {{ $failed->count() ?? 0 }} Failed</span>
    </div>

    {{-- ═══ KANBAN BOARD ═══ --}}
    <div class="row g-3 stagger-3" style="opacity: 0;">

        {{-- TO DO --}}
        <div class="col-12 col-md-4">
            <div class="kanban-col">
                <div class="kanban-col-header">
                    <span class="kanban-col-title" style="color: var(--text-muted);">
                        <i class="fa-regular fa-circle me-2"></i>To Do
                    </span>
                    <span class="kanban-col-count" style="background: rgba(255,255,255,0.06); color: var(--text-muted);">{{ $todo->count() }}</span>
                </div>
                <div class="flex-1" id="col-todo">
                    @forelse($todo as $task)
                    @include('hubs.partials.task-card', ['task' => $task])
                    @empty
                    <div class="text-center py-4 text-muted" style="font-size: 0.78rem;">
                        <i class="fa-regular fa-circle-dot mb-2 d-block" style="font-size: 1.5rem; color: var(--glass-border);"></i>
                        No pending tasks
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- IN PROGRESS --}}
        <div class="col-12 col-md-4">
            <div class="kanban-col" style="border-color: hsla(174,90%,41%,0.2);">
                <div class="kanban-col-header">
                    <span class="kanban-col-title" style="color: var(--nexus-teal);">
                        <i class="fa-solid fa-spinner fa-spin me-2"></i>In Progress
                    </span>
                    <span class="kanban-col-count" style="background: var(--nexus-teal-dim); color: var(--nexus-teal);">{{ $inProgress->count() }}</span>
                </div>
                <div class="flex-1" id="col-in-progress">
                    @forelse($inProgress as $task)
                    @include('hubs.partials.task-card', ['task' => $task])
                    @empty
                    <div class="text-center py-4 text-muted" style="font-size: 0.78rem;">
                        <i class="fa-solid fa-pause mb-2 d-block" style="font-size: 1.5rem; color: var(--glass-border);"></i>
                        No active tasks
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- COMPLETED --}}
        <div class="col-12 col-md-4">
            <div class="kanban-col" style="border-color: hsla(142,72%,29%,0.2);">
                <div class="kanban-col-header">
                    <span class="kanban-col-title" style="color: var(--success-bright);">
                        <i class="fa-solid fa-circle-check me-2"></i>Completed
                    </span>
                    <span class="kanban-col-count" style="background: hsla(142,72%,29%,0.15); color: var(--success-bright);">{{ $completed->count() }}</span>
                </div>
                <div class="flex-1" id="col-completed">
                    @forelse($completed as $task)
                    @include('hubs.partials.task-card', ['task' => $task])
                    @empty
                    <div class="text-center py-4 text-muted" style="font-size: 0.78rem;">
                        <i class="fa-solid fa-trophy mb-2 d-block" style="font-size: 1.5rem; color: var(--glass-border);"></i>
                        No completed tasks
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ═══ NEW TASK MODAL ═══ --}}
<div class="modal fade" id="newTaskModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="fa-solid fa-plus" style="color: var(--nexus-teal);"></i> New Task
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="new-task-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Task Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="Describe the task..." required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Task details and objectives..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Type</label>
                            <select name="type" class="form-select">
                                <option value="manual">Manual</option>
                                <option value="agent">Agent</option>
                                <option value="system">System</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Due Date</label>
                            <input type="datetime-local" name="due_at" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Payload (JSON)</label>
                            <textarea name="payload" class="form-control" rows="3" placeholder='{"key": "value"}' style="font-family: 'JetBrains Mono'; font-size: 0.75rem;"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-secondary); border-radius: 7px;" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="btn-create-task" style="border-radius: 7px;">
                    <i class="fa-solid fa-check me-1"></i> Create Task
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ═══ TASK EXECUTION LOG MODAL ═══ --}}
<div class="modal fade" id="taskExecModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="agent-status-orb busy" style="width: 8px; height: 8px;"></span>
                    Task Execution: <span id="task-exec-name" class="text-primary ms-1"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="exec-log-terminal" id="task-exec-terminal"></div>
                <div class="mt-3">
                    <div style="height: 4px; background: rgba(255,255,255,0.06); border-radius: 2px; overflow: hidden;">
                        <div id="task-exec-bar" style="height: 100%; width: 0%; background: linear-gradient(90deg, var(--nexus-blue), var(--nexus-teal)); border-radius: 2px; transition: width 0.4s ease;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-secondary); border-radius: 7px;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {

    // ─── Create Task ───
    $('#btn-create-task').on('click', function() {
        const form = $('#new-task-form');
        const data = form.serialize();
        const $btn = $(this);
        $btn.html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Creating...').prop('disabled', true);

        $.ajax({
            url: '{{ route("hub.tasks") }}',
            method: 'POST',
            data: data,
            success: function(res) {
                Nexus.notify('Task created!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('newTaskModal')).hide();
                setTimeout(() => window.location.reload(), 600);
            },
            error: function() {
                Nexus.notify('Failed to create task. Check the form.', 'error');
                $btn.html('<i class="fa-solid fa-check me-1"></i> Create Task').prop('disabled', false);
            }
        });
    });

    // ─── Execute Task ───
    window.executeTask = function(id, title) {
        $('#task-exec-name').text(title);
        $('#task-exec-terminal').html('');
        $('#task-exec-bar').css('width', '0%');
        $('#taskExecModal').modal('show');

        Nexus.updateStatusBar('Executing task: ' + title, 'running');

        const logs = [
            { cls: 'exec-line-info',    text: `[TASK] Initializing task execution...` },
            { cls: 'exec-line-info',    text: `[TASK] Loading task parameters and payload...` },
            { cls: 'exec-line-info',    text: `[TASK] Assigning to agent queue...` },
            { cls: 'exec-line-success', text: `[TASK] Agent picked up task #${id}` },
            { cls: 'exec-line-info',    text: `[AGENT] Running: ${title}` },
            { cls: 'exec-line-success', text: `[AGENT] Step 1 of 3: Gathering context... done.` },
            { cls: 'exec-line-success', text: `[AGENT] Step 2 of 3: Processing... done.` },
            { cls: 'exec-line-success', text: `[AGENT] Step 3 of 3: Finalizing... done.` },
            { cls: 'exec-line-success', text: `[TASK] Completed successfully.` },
        ];

        let i = 0;
        const $terminal = $('#task-exec-terminal');
        const interval = setInterval(() => {
            if (i >= logs.length) {
                clearInterval(interval);
                Nexus.updateStatusBar('Task completed: ' + title, 'success');
                setTimeout(() => Nexus.clearStatusBar(), 3000);
                return;
            }
            $terminal.append(`<span class="exec-line ${logs[i].cls}">${logs[i].text}</span>\n`);
            $terminal.scrollTop($terminal[0].scrollHeight);
            $('#task-exec-bar').css('width', Math.round(((i+1)/logs.length)*100) + '%');
            i++;
        }, 500);

        // Listen for real-time events
        window.Echo.channel(`task.${id}`)
            .listen('TaskProgressUpdated', (e) => {
                $terminal.append(`<span class="exec-line exec-line-info">[REALTIME] ${e.message}</span>\n`);
                $terminal.scrollTop($terminal[0].scrollHeight);
                if (e.progress) $('#task-exec-bar').css('width', e.progress + '%');
            });
    };

});
</script>
@endpush
