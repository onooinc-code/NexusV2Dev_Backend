<?php

namespace App\Jobs\HedraSoul;

use App\Models\HedrasoulApprovalRequest;
use App\Services\HedraSoul\HedraSoulRealtimeBroadcaster;
use App\Services\HedraSoul\HedraSoulNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * DispatchApprovalReminderJob: Sends reminder for deferred approval requests.
 * 
 * Scheduled by ApprovalInboxService::defer() after user defers an approval decision.
 * Checks if approval is still in 'deferred' status and broadcasts a reminder notification
 * to prompt the user to revisit the approval decision.
 */
class DispatchApprovalReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(
        public HedrasoulApprovalRequest $request,
        public string $deferDuration
    ) {}

    public function handle(): void
    {
        try {
            // Refresh the approval request from database to get current status
            $this->request->refresh();

            // Only proceed if still deferred
            if ($this->request->status !== 'deferred') {
                \Log::info('DispatchApprovalReminderJob skipped: approval no longer deferred', [
                    'approval_id' => $this->request->id,
                    'current_status' => $this->request->status,
                ]);
                return;
            }

            // Determine the user who should receive the reminder
            $userId = $this->request->decided_by ?? auth()->id();

            // Broadcast reminder via notification service (creates DB record + broadcasts)
            app(\App\Services\HedraSoul\HedraSoulNotificationService::class)->create(
                type: 'approval_reminder',
                priority: 'medium',
                title: 'Approval Reminder',
                body: 'Your deferred approval request needs your attention: ' . $this->request->action_description,
                relatedId: $this->request->id,
                relatedType: 'approval_request',
                actionButtons: [
                    ['label' => 'Review', 'action' => 'view_approval'],
                    ['label' => 'Dismiss', 'action' => 'dismiss'],
                ],
            );

            \Log::info('DispatchApprovalReminderJob completed', [
                'approval_id' => $this->request->id,
                'user_id' => $userId,
            ]);

        } catch (Throwable $e) {
            $this->failed($e);
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $e): void
    {
        \Log::warning('DispatchApprovalReminderJob failed', [
            'approval_id' => $this->request->id,
            'error' => $e->getMessage(),
        ]);

        // Non-critical path - don't interrupt user experience
        // The reminder can be retried on next defer cycle
    }
}
