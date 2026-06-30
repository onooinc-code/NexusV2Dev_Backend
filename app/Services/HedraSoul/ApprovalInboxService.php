<?php

namespace App\Services\HedraSoul;

use App\Models\HedrasoulApprovalRequest;
use App\Jobs\HedraSoul\ExecuteSoulyCommandJob;
use App\Jobs\HedraSoul\DispatchApprovalReminderJob;
use Illuminate\Support\Facades\Auth;

/**
 * ApprovalInboxService: Manages approval requests for risky/medium/external actions.
 * Provides create, approve, reject, defer lifecycle methods.
 */
class ApprovalInboxService
{
    /**
     * Create a new approval request (status = pending).
     */
    public function create(array $data): HedrasoulApprovalRequest
    {
        $data['status'] = 'pending';

        return HedrasoulApprovalRequest::create($data);
    }

    /**
     * Approve an approval request and dispatch the queued action.
     */
    public function approve(HedrasoulApprovalRequest $req, int $userId, ?string $notes = null): void
    {
        $req->update([
            'status' => 'approved',
            'decided_by' => $userId,
            'decided_at' => now(),
            'decision_notes' => $notes,
        ]);

        // Dispatch job to execute the approved command
        ExecuteSoulyCommandJob::dispatch($req);

        // Broadcast approval event
        app(HedraSoulRealtimeBroadcaster::class)->broadcastApprovalApproved([
            'approval_id' => $req->id,
            'decided_by' => $userId,
            'decided_at' => now()->toIso8601String(),
        ], auth()->id());
    }

    /**
     * Reject an approval request (action will not execute).
     */
    public function reject(HedrasoulApprovalRequest $req, int $userId, ?string $notes = null): void
    {
        $req->update([
            'status' => 'rejected',
            'decided_by' => $userId,
            'decided_at' => now(),
            'decision_notes' => $notes,
        ]);

        // Broadcast rejection event
        app(HedraSoulRealtimeBroadcaster::class)->broadcastApprovalRejected([
            'approval_id' => $req->id,
            'decided_by' => $userId,
            'decided_at' => now()->toIso8601String(),
        ], auth()->id());
    }

    /**
     * Defer an approval request to be reminded later.
     * Schedules DispatchApprovalReminderJob for specified duration.
     */
    public function defer(HedrasoulApprovalRequest $req, string $duration): void
    {
        $req->update(['status' => 'deferred']);

        // Schedule reminder job
        DispatchApprovalReminderJob::dispatch($req, $duration)
            ->delay(now()->add($this->parseDuration($duration)));
    }

    /**
     * Get pending approvals for authenticated user.
     */
    public function getPendingApprovals($limit = 50)
    {
        return HedrasoulApprovalRequest::pending()
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Get all approvals for authenticated user with optional status filter.
     */
    public function getApprovals(?string $status = null, $limit = 50)
    {
        $query = HedrasoulApprovalRequest::query();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Get single approval request.
     */
    public function getApproval(int $id): ?HedrasoulApprovalRequest
    {
        return HedrasoulApprovalRequest::find($id);
    }

    /**
     * Parse duration string (e.g., "2h", "1d") to interval.
     */
    protected function parseDuration(string $duration): \DateInterval
    {
        // Simple parser for common formats: 1h, 2d, 30m
        if (preg_match('/^(\d+)([hmd])$/', $duration, $matches)) {
            $value = (int) $matches[1];
            $unit = $matches[2];

            switch ($unit) {
                case 'h':
                    return new \DateInterval("PT{$value}H");
                case 'd':
                    return new \DateInterval("P{$value}D");
                case 'm':
                    return new \DateInterval("PT{$value}M");
            }
        }

        // Default to 1 hour
        return new \DateInterval("PT1H");
    }
}
