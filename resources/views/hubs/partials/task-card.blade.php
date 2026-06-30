{{-- Task Card Partial: resources/views/hubs/partials/task-card.blade.php --}}
@php
    $priority = strtolower($task->priority ?? 'medium');
    $priorityIcons = ['critical' => '🔴', 'high' => '🔴', 'medium' => '🟡', 'low' => '🟢'];
    $priorityIcon = $priorityIcons[$priority] ?? '⚪';
    $status = strtolower($task->status ?? 'pending');
    $progress = $task->progress ?? ($status === 'completed' || $status === 'done' ? 100 : ($status === 'running' || $status === 'in_progress' ? rand(30, 70) : 0));
@endphp
<div class="task-card priority-{{ $priority }}" id="task-{{ $task->id }}">
    <div class="d-flex align-items-start justify-content-between mb-2">
        <span class="priority-badge {{ $priority }}">{{ $priorityIcon }} {{ $priority }}</span>
        <div class="d-flex gap-1">
            @if(in_array($status, ['pending', 'queued', 'todo']))
            <button class="btn btn-sm p-1" style="font-size: 0.68rem; background: var(--nexus-blue-dim); border: 1px solid var(--nexus-blue-glow); color: var(--nexus-blue); border-radius: 5px; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center;"
                    title="Execute" onclick="executeTask({{ $task->id }}, '{{ addslashes($task->title) }}')">
                <i class="fa-solid fa-play"></i>
            </button>
            @endif
            <button class="btn btn-sm p-1" style="font-size: 0.68rem; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); color: var(--text-muted); border-radius: 5px; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center;"
                    title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
        </div>
    </div>
    <div class="fw-semibold mb-1" style="font-size: 0.85rem; color: var(--text-primary);">{{ $task->title }}</div>
    @if($task->description)
    <div class="text-muted mb-2" style="font-size: 0.75rem; line-height: 1.5;">{{ Str::limit($task->description, 80) }}</div>
    @endif
    <div class="d-flex align-items-center justify-content-between mb-2" style="font-size: 0.68rem; color: var(--text-muted); font-family: 'JetBrains Mono';">
        <span><i class="fa-solid fa-tag me-1"></i>{{ $task->type ?? 'manual' }}</span>
        @if($task->due_at)
        <span><i class="fa-regular fa-clock me-1"></i>{{ \Carbon\Carbon::parse($task->due_at)->diffForHumans() }}</span>
        @endif
    </div>
    <div style="height: 3px; background: rgba(255,255,255,0.06); border-radius: 2px; overflow: hidden; margin-top: 6px;">
        <div style="height: 100%; width: {{ $progress }}%; background: linear-gradient(90deg, var(--nexus-blue), var(--nexus-teal)); border-radius: 2px; transition: width 0.5s ease;"></div>
    </div>
    <div class="task-progress"></div>
</div>
