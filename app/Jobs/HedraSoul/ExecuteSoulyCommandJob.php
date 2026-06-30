<?php

namespace App\Jobs\HedraSoul;

use App\Models\HedrasoulApprovalRequest;
use App\Services\HedraSoul\SoulyTraceService;
use App\Services\HedraSoul\HedraSoulRealtimeBroadcaster;
use App\Services\HedraSoul\HedraSoulNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * ExecuteSoulyCommandJob: Executes an approved command.
 * 
 * Called by ApprovalInboxService::approve() after user approval.
 * Executes the approved action via AgentsHub, records trace, broadcasts completion.
 */
class ExecuteSoulyCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public HedrasoulApprovalRequest $approvalRequest) {}

    public function handle(): void
    {
        try {
            // Step 1: Retrieve the approved action payload
            $payload = $this->approvalRequest->inputs;
            $actionDescription = $this->approvalRequest->action_description;

            // Step 2: Execute the command via AgentsHub
            // TODO: Implement actual AgentsHub execution
            $executionResult = $this->executeViaAgentsHub($payload, $actionDescription);

            // Step 3: Record trace
            $traceService = app(SoulyTraceService::class);
            $trace = $traceService->record([
                'message_id' => $this->approvalRequest->source_id ? 
                    (is_numeric($this->approvalRequest->source_id) ? $this->approvalRequest->source_id : null) : null,
                'trace_id' => $executionResult['trace_id'] ?? \Str::uuid(),
                'parsed_intent' => $this->approvalRequest->action_description,
                'selected_action' => $this->approvalRequest->source_type,
                'model_instance_id' => null,
                'agent_id' => null,
                'instruction_version_id' => null,
                'context_snapshot_id' => $this->approvalRequest->context_snapshot_id,
                'tools_invoked' => $executionResult['tools_invoked'] ?? [],
                'tasks_created' => $executionResult['tasks_created'] ?? [],
                'workflows_triggered' => $executionResult['workflows_triggered'] ?? [],
                'approval_decision' => $this->approvalRequest->id,
                'final_output' => $executionResult['output'] ?? 'Command executed successfully',
                'cost_usd' => $executionResult['cost_usd'] ?? 0,
                'duration_ms' => $executionResult['duration_ms'] ?? 0,
                'errors' => $executionResult['errors'] ?? [],
            ]);

            // Step 4: Broadcast command executed event
            app(HedraSoulRealtimeBroadcaster::class)->broadcastCommandExecuted([
                'trace_id' => $trace->trace_id,
                'selected_action' => $this->approvalRequest->source_type,
                'tasks_created' => $executionResult['tasks_created'] ?? [],
                'workflows_triggered' => $executionResult['workflows_triggered'] ?? [],
            ], $this->approvalRequest->decided_by ?? auth()->id());

        } catch (Throwable $e) {
            $this->failed($e);
            throw $e;
        }
    }

    /**
     * Execute action via AgentsHub.
     * 
     * This is a placeholder - integrate with actual AgentsHub implementation.
     */
    private function executeViaAgentsHub(array $payload, string $actionDescription): array
    {
        // TODO: Implement actual AgentsHub execution
        return [
            'trace_id' => \Str::uuid(),
            'selected_action' => 'executed',
            'tools_invoked' => [],
            'tasks_created' => [],
            'workflows_triggered' => [],
            'output' => 'Command executed successfully',
            'cost_usd' => 0.02,
            'duration_ms' => 1000,
            'errors' => [],
        ];
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $e): void
    {
        \Log::error('ExecuteSoulyCommandJob failed', [
            'approval_request_id' => $this->approvalRequest->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Update approval request status to failed
        $this->approvalRequest->update([
            'status' => 'failed',
            'decision_notes' => 'Execution failed: ' . $e->getMessage(),
        ]);

        // Create failure notification
        app(HedraSoulNotificationService::class)->create(
            type: 'agent_failure',
            priority: 'high',
            title: 'Command Execution Failed',
            body: 'Failed to execute the approved command: ' . $e->getMessage(),
            relatedId: $this->approvalRequest->id,
            relatedType: 'approval_request',
        );

        // notification service already broadcast the event above
    }
}

