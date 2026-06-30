<?php

namespace App\Jobs\HedraSoul;

use App\Models\HedrasoulMessage;
use App\Services\HedraSoul\CommandIntent;
use App\Services\HedraSoul\SoulyCommandRouter;
use App\Services\HedraSoul\SoulyActionPolicyService;
use App\Services\HedraSoul\SoulyContextAssembler;
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
 * ProcessHedraSoulMessageJob: Main message processing pipeline.
 * 
 * Classifies intent → checks policy → assembles context → invokes AgentsHub →
 * records trace → dispatches follow-up jobs → broadcasts completion event.
 */
class ProcessHedraSoulMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public HedrasoulMessage $message) {}

    public function handle(): void
    {
        try {
            // Step 1: Classify command intent
            $commandRouter = app(SoulyCommandRouter::class);
            $commandIntent = $commandRouter->classify($this->message);
            
            $this->message->update([
                'intent' => $commandIntent->intent,
                'risk_level' => $commandIntent->riskLevel,
                'status' => 'processing',
            ]);

            // Step 2: Check autonomy policy
            $policyService = app(SoulyActionPolicyService::class);
            $policyResult = $policyService->canExecute($commandIntent->intent, $commandIntent->riskLevel);

            if (!$policyResult->allowed) {
                // Policy blocked - save status and broadcast failure
                $this->message->update(['status' => 'blocked']);
                
                app(HedraSoulRealtimeBroadcaster::class)->broadcastCommandDetected([
                    'message_id' => $this->message->id,
                    'intent' => $commandIntent->intent,
                    'risk_level' => $commandIntent->riskLevel,
                    'policy_result' => $policyResult,
                    'blocked' => true,
                ], $this->message->sender_id ?? auth()->id());

                return;
            }

            // Step 3: Assemble full context snapshot
            $contextAssembler = app(SoulyContextAssembler::class);
            $contextSnapshot = $contextAssembler->assemble(
                $this->message->session,
                $this->message
            );

            // Step 4: Invoke AgentsHub / AiModelsHub
            // This is a placeholder - actual implementation depends on AgentsHub API
            $agentResponse = $this->invokeAgentsHub($contextSnapshot, $commandIntent);

            // Step 5: Record trace
            $traceService = app(SoulyTraceService::class);
            $trace = $traceService->record([
                'message_id' => $this->message->id,
                'trace_id' => $agentResponse['trace_id'] ?? \Str::uuid(),
                'parsed_intent' => $commandIntent->intent,
                'selected_action' => $agentResponse['selected_action'] ?? null,
                'model_instance_id' => $agentResponse['model_instance_id'] ?? null,
                'agent_id' => $agentResponse['agent_id'] ?? null,
                'instruction_version_id' => $agentResponse['instruction_version_id'] ?? null,
                'context_snapshot_id' => $contextSnapshot->id,
                'tools_invoked' => $agentResponse['tools_invoked'] ?? [],
                'tasks_created' => $agentResponse['tasks_created'] ?? [],
                'workflows_triggered' => $agentResponse['workflows_triggered'] ?? [],
                'approval_decision' => null,
                'final_output' => $agentResponse['output'] ?? null,
                'cost_usd' => $agentResponse['cost_usd'] ?? 0,
                'duration_ms' => $agentResponse['duration_ms'] ?? 0,
                'errors' => $agentResponse['errors'] ?? [],
            ]);

            // Step 6: Update message with processing results
            $this->message->update([
                'status' => 'processed',
                'trace_id' => $trace->trace_id,
                'model_instance_id' => $agentResponse['model_instance_id'] ?? null,
                'token_count' => $agentResponse['token_count'] ?? 0,
                'cost_usd' => $agentResponse['cost_usd'] ?? 0,
            ]);

            // Step 7: Dispatch follow-up jobs
            AnalyzeHedraSoulMessageJob::dispatch($this->message);
            CreateHedraMemorySuggestionJob::dispatch($this->message);

            // Step 8: Broadcast completion
            app(HedraSoulRealtimeBroadcaster::class)->broadcastMessageProcessed(
                $this->message,
                $this->message->sender_id ?? auth()->id()
            );

        } catch (Throwable $e) {
            $this->failed($e);
            throw $e;
        }
    }

    /**
     * Invoke AgentsHub with the assembled context.
     * 
     * This is a placeholder implementation - the actual AgentsHub API should be called here.
     */
    private function invokeAgentsHub($contextSnapshot, CommandIntent $commandIntent): array
    {
        // TODO: Implement actual AgentsHub invocation
        // For now, return mock response
        return [
            'trace_id' => \Str::uuid(),
            'selected_action' => $commandIntent->intent ?? 'answer',
            'model_instance_id' => null,
            'agent_id' => null,
            'instruction_version_id' => null,
            'tools_invoked' => [],
            'tasks_created' => [],
            'workflows_triggered' => [],
            'output' => 'Response from Souly',
            'cost_usd' => 0.01,
            'duration_ms' => 500,
            'errors' => [],
            'token_count' => 150,
        ];
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $e): void
    {
        \Log::error('ProcessHedraSoulMessageJob failed', [
            'message_id' => $this->message->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Set message status to failed
        $this->message->update(['status' => 'failed']);

        // Create failure notification
        app(HedraSoulNotificationService::class)->create(
            type: 'agent_failure',
            priority: 'high',
            title: 'Message Processing Failed',
            body: 'Failed to process your message. Please try again or contact support.',
            relatedId: $this->message->id,
            relatedType: 'message',
        );

        // notification service already broadcast the event above
    }
}
