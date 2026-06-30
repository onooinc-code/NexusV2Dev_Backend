@extends('layouts.app')

@section('content')
<div class="row mb-4 align-items-center animate-fade-in stagger-1">
    <div class="col-md-6">
        <h2 class="fw-bold mb-0">LogsHub</h2>
        <p class="text-muted">System Logs & Audit Trails.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <button class="btn btn-outline-warning me-2" id="btn-test-error">
            <i class="fa-solid fa-bug me-1"></i> Raise Test Error
        </button>
    </div>
</div>

<div class="card bg-dark border-secondary hover-3d h-100 shadow-lg animate-fade-in stagger-2" style="min-height: 600px; background: rgba(22, 27, 34, 0.5) !important; backdrop-filter: blur(10px);">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center p-3 bg-transparent">
        <div>
            <span class="badge bg-info bg-opacity-25 text-info border border-info me-2 cursor-pointer toggle-log" data-level="INFO">INFO</span>
            <span class="badge bg-warning bg-opacity-25 text-warning border border-warning me-2 cursor-pointer toggle-log" data-level="WARN">WARNING</span>
            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger cursor-pointer toggle-log" data-level="ERROR">ERROR</span>
        </div>
        <div>
            <button class="btn btn-sm btn-outline-success me-1" id="btn-toggle-feed"><i class="fa-solid fa-pause"></i> Pause Feed</button>
            <button class="btn btn-sm btn-outline-danger" id="btn-clear-logs"><i class="fa-solid fa-eraser"></i> Clear</button>
        </div>
    </div>
    <div class="card-body p-0 bg-transparent">
        <div id="log-console" class="p-4" style="height: 500px; overflow-y: auto; font-family: 'Courier New', Courier, monospace; font-size: 0.9rem; background-color: #010409;">
            <div class="text-success fw-bold mb-3">-- NEXUS AUDIT TRAIL INITIALIZED --</div>
            <div class="log-entry"><span class="log-time">[2026-06-21 10:00:01]</span><span class="text-info fw-bold">[INFO]</span> <span class="text-light">System booted successfully. All cognitive modules online.</span></div>
            <div class="log-entry"><span class="log-time">[2026-06-21 10:05:22]</span><span class="text-warning fw-bold">[WARN]</span> <span class="text-light">Rate limiting threshold approached for GPT-4 endpoint.</span></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    .cursor-pointer { cursor: pointer; }
    .log-entry { padding: 4px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .log-time { color: #8b949e; margin-right: 10px; }
</style>
<script>
    $(document).ready(function() {
        const consoleEl = $('#log-console');
        let feedActive = true;
        let counter = 0;

        function addLog(level, message) {
            if (!feedActive) return;
            
            const time = new Date().toISOString().replace('T', ' ').substring(0, 23);
            let colorClass = 'text-info';
            if (level === 'WARN') colorClass = 'text-warning';
            if (level === 'ERROR') colorClass = 'text-danger';

            const logHtml = `
                <div class="log-entry" data-level="${level}">
                    <span class="log-time">[${time}]</span>
                    <span class="fw-bold ${colorClass}">[${level}]</span>
                    <span class="text-light ms-2">${message}</span>
                </div>
            `;
            consoleEl.append(logHtml);
            consoleEl.scrollTop(consoleEl[0].scrollHeight);
        }

        // Mock Polling
        const logMessages = [
            { l: 'INFO', m: 'Request received from 192.168.1.1' },
            { l: 'INFO', m: 'Agent Core routed prompt successfully.' },
            { l: 'WARN', m: 'Response time exceeded 500ms on Anthropic API.' },
            { l: 'INFO', m: 'Database query executed in 12ms.' },
            { l: 'INFO', m: 'User session validated.' },
        ];

        setInterval(() => {
            counter++;
            // Inject random log
            const r = logMessages[Math.floor(Math.random() * logMessages.length)];
            addLog(r.l, r.m);
        }, 2000);

        // Test Error
        $('#btn-test-error').click(function() {
            addLog('ERROR', 'Manual Exception triggered! Undefined variable $mockData in NexusController on line 42.');
        });

        // Toggle Feed
        $('#btn-toggle-feed').click(function() {
            feedActive = !feedActive;
            if(feedActive) {
                $(this).html('<i class="fa-solid fa-pause"></i> Pause Feed').removeClass('btn-outline-primary').addClass('btn-outline-success');
            } else {
                $(this).html('<i class="fa-solid fa-play"></i> Resume Feed').removeClass('btn-outline-success').addClass('btn-outline-primary');
            }
        });

        // Clear Logs
        $('#btn-clear-logs').click(function() {
            consoleEl.html('<div class="text-muted mb-2">-- NEXUS AUDIT TRAIL CLEARED --</div>');
        });

        // Filter Logs (Simple Hide/Show based on opacity toggle of badges)
        $('.toggle-log').click(function() {
            $(this).toggleClass('opacity-50');
            const level = $(this).data('level');
            if($(this).hasClass('opacity-50')) {
                $(`.log-entry[data-level="${level}"]`).hide();
            } else {
                $(`.log-entry[data-level="${level}"]`).show();
            }
        });
    });
</script>
@endpush
