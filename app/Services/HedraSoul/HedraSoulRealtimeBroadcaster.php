<?php

namespace App\Services\HedraSoul;

/**
 * HedraSoulRealtimeBroadcaster: Broadcasts all HedraSoulHub realtime events to Reverb.
 * Each event is broadcast on the private channel hedrasoul.hub.{user_id}.
 */
class HedraSoulRealtimeBroadcaster
{
    /**
     * Broadcast message created event.
     */
    public function broadcastMessageCreated(\App\Models\HedrasoulMessage $message, ?int $userId): void
    {
        if ($userId === null) return;
        try {
            broadcast(new \App\Events\HedraSoul\HedraSoulMessageCreated($message, $userId))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast message created: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast message processed event.
     */
    public function broadcastMessageProcessed(\App\Models\HedrasoulMessage $message, ?int $userId): void
    {
        if ($userId === null) return;
        try {
            broadcast(new \App\Events\HedraSoul\HedraSoulMessageProcessed($message, $userId))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast message processed: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast command detected event.
     */
    public function broadcastCommandDetected(array $payload, ?int $userId): void
    {
        if ($userId === null) return;
        try {
            broadcast(new \App\Events\HedraSoul\HedraSoulCommandDetected(
                $userId,
                $payload['message_id'] ?? 0,
                $payload['intent'] ?? '',
                $payload['risk_level'] ?? 'low',
                (array) ($payload['policy_result'] ?? [])
            ))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast command detected: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast command executed event.
     */
    public function broadcastCommandExecuted(array $payload, ?int $userId): void
    {
        if ($userId === null) return;
        try {
            broadcast(new \App\Events\HedraSoul\HedraSoulCommandExecuted(
                $userId,
                $payload['trace_id'] ?? '',
                $payload['selected_action'] ?? '',
                $payload['tasks_created'] ?? null,
                $payload['workflows_triggered'] ?? null
            ))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast command executed: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast approval requested event.
     */
    public function broadcastApprovalRequested(\App\Models\HedrasoulApprovalRequest $approval, ?int $userId): void
    {
        if ($userId === null) return;
        try {
            broadcast(new \App\Events\HedraSoul\HedraSoulApprovalRequested($userId, $approval))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast approval requested: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast approval approved event.
     */
    public function broadcastApprovalApproved(array $payload, ?int $userId): void
    {
        if ($userId === null) return;
        try {
            broadcast(new \App\Events\HedraSoul\HedraSoulApprovalApproved(
                $userId,
                $payload['approval_id'] ?? 0,
                $payload['decided_by'] ?? null
            ))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast approval approved: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast approval rejected event.
     */
    public function broadcastApprovalRejected(array $payload, ?int $userId): void
    {
        if ($userId === null) return;
        try {
            broadcast(new \App\Events\HedraSoul\HedraSoulApprovalRejected(
                $userId,
                $payload['approval_id'] ?? 0,
                $payload['decided_by'] ?? null
            ))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast approval rejected: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast instruction changed event.
     */
    public function broadcastInstructionChanged(array $payload, ?int $userId): void
    {
        if ($userId === null) return;
        try {
            broadcast(new \App\Events\HedraSoul\HedraSoulInstructionChanged(
                $userId,
                $payload['version_id'] ?? 0,
                $payload['version_number'] ?? 0,
                $payload['status'] ?? 'active',
                $payload['activated_by'] ?? null
            ))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast instruction changed: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast model changed event.
     */
    public function broadcastModelChanged(array $payload, ?int $userId): void
    {
        if ($userId === null) return;
        try {
            broadcast(new \App\Events\HedraSoul\HedraSoulModelChanged(
                $userId,
                $payload['model_instance_id'] ?? 0,
                $payload['changed_at'] ?? null
            ))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast model changed: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast memory suggested event.
     */
    public function broadcastMemorySuggested(array $payload, ?int $userId): void
    {
        if ($userId === null) return;
        try {
            broadcast(new \App\Events\HedraSoul\HedraSoulMemorySuggested(
                $userId,
                $payload['suggestion_id'] ?? 0,
                $payload['memory_type'] ?? ''
            ))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast memory suggested: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast memory approved event.
     */
    public function broadcastMemoryApproved(array $payload, ?int $userId): void
    {
        if ($userId === null) return;
        try {
            broadcast(new \App\Events\HedraSoul\HedraSoulMemoryApproved(
                $userId,
                $payload['fact_id'] ?? 0,
                $payload['suggestion_id'] ?? 0,
                $payload['memory_type'] ?? ''
            ))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast memory approved: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast autonomy changed event.
     */
    public function broadcastAutonomyChanged(array $payload, ?int $userId): void
    {
        if ($userId === null) return;
        try {
            broadcast(new \App\Events\HedraSoul\HedraSoulAutonomyChanged(
                $userId,
                $payload['autonomy_mode'] ?? 'unknown',
                $payload['changed_by'] ?? null,
                $payload['changed_at'] ?? null
            ))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast autonomy changed: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast notification created event.
     */
    public function broadcastNotificationCreated(\App\Models\HedrasoulNotification $notification, ?int $userId): void
    {
        if ($userId === null) return;
        try {
            broadcast(new \App\Events\HedraSoul\HedraSoulNotificationCreated($userId, $notification))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast notification created: ' . $e->getMessage());
        }
    }
}
