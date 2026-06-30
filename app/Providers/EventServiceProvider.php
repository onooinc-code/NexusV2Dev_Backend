<?php

namespace App\Providers;

use App\Events\TaskCompletedEvent;
use App\Events\TaskFailedEvent;
use App\Events\TaskStatusChangedEvent;
use App\Events\TaskMovedToDLQEvent;
use App\Listeners\HandleTaskCompleted;
use App\Listeners\HandleTaskFailed;
use App\Listeners\LogDeadLetterTask;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        TaskCompletedEvent::class => [
            HandleTaskCompleted::class,
        ],
        TaskFailedEvent::class => [
            HandleTaskFailed::class,
        ],
        TaskMovedToDLQEvent::class => [
            LogDeadLetterTask::class,
        ],
        // TaskStatusChangedEvent can be handled by adding listeners as needed
        \App\Events\ContactImportCompleted::class => [
            \App\Listeners\HandleContactImportCompleted::class,
        ],
        \App\Events\ContactAnalysisCompleted::class => [
            \App\Listeners\HandleContactAnalysisCompleted::class,
        ],
        \App\Events\ContactIdentityConflictDetected::class => [
            \App\Listeners\HandleContactIdentityConflict::class,
        ],
        \App\Events\ContactReplyModeChanged::class => [
            \App\Listeners\HandleContactReplyModeChanged::class,
        ],
        // HedraSoul Hub Events (13 broadcasting events)
        \App\Events\HedraSoul\HedraSoulMessageCreated::class => [],
        \App\Events\HedraSoul\HedraSoulMessageProcessed::class => [],
        \App\Events\HedraSoul\HedraSoulCommandDetected::class => [],
        \App\Events\HedraSoul\HedraSoulCommandExecuted::class => [],
        \App\Events\HedraSoul\HedraSoulApprovalRequested::class => [],
        \App\Events\HedraSoul\HedraSoulApprovalApproved::class => [],
        \App\Events\HedraSoul\HedraSoulApprovalRejected::class => [],
        \App\Events\HedraSoul\HedraSoulInstructionChanged::class => [],
        \App\Events\HedraSoul\HedraSoulModelChanged::class => [],
        \App\Events\HedraSoul\HedraSoulMemorySuggested::class => [],
        \App\Events\HedraSoul\HedraSoulMemoryApproved::class => [],
        \App\Events\HedraSoul\HedraSoulAutonomyChanged::class => [],
        \App\Events\HedraSoul\HedraSoulNotificationCreated::class => [],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        // Handle task status changes from the Task model
        \App\Models\AgentTask::updated(function ($task) {
            // Check if status changed
            if ($task->wasChanged('status')) {
                event(new \App\Events\TaskStatusChangedEvent(
                    $task,
                    $task->getOriginal('status'),
                    $task->status
                ));
            }
        });
    }

    /**
     * Register any events with broadcasting.
     */
    public function broadcastOn(): void
    {
        // Events are broadcasted via the event classes themselves
    }
}