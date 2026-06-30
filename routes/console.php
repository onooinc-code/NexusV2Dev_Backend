<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('ai:poll-health')->everyFiveMinutes();
\Illuminate\Support\Facades\Schedule::command('ai:rotate-keys')->daily();

\Illuminate\Support\Facades\Schedule::call(function () {
    app(\App\Services\TaskSchedulingService::class)->processDueTasks();
})->everyMinute();

\Illuminate\Support\Facades\Schedule::call(function () {
    app(\App\Services\Workflows\WorkflowScheduleService::class)->processScheduledWorkflows();
})->everyMinute();

\Illuminate\Support\Facades\Schedule::command('monitor:reverb-health')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->description('Run periodic Reverb WebSocket health checks.');

\Illuminate\Support\Facades\Schedule::command('proactive:run-scheduler')
    ->everyMinute()
    ->withoutOverlapping()
    ->description('Run proactive AI autonomous trigger scheduler.');

\Illuminate\Support\Facades\Schedule::command('monitor:settings-health')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->description('Run periodic health checks for settings and integration credentials.');

// PeopleConnect Scheduled Jobs
\Illuminate\Support\Facades\Schedule::job(new \App\Jobs\PeopleConnect\SyncWahaContactsJob(), null, 'peopleconnect')->hourly();
\Illuminate\Support\Facades\Schedule::job(new \App\Jobs\PeopleConnect\SyncWahaConversationsJob(), null, 'peopleconnect')->hourly();
\Illuminate\Support\Facades\Schedule::job(new \App\Jobs\PeopleConnect\SyncWahaMessagesJob(), null, 'peopleconnect')->hourly();
\Illuminate\Support\Facades\Schedule::job(new \App\Jobs\PeopleConnect\ReconcileWahaDeliveryStatusJob(), null, 'peopleconnect')->hourly();
\Illuminate\Support\Facades\Schedule::job(new \App\Jobs\PeopleConnect\CloseInactivePeopleConnectSessionsJob(), null, 'peopleconnect')->everyFifteenMinutes();
