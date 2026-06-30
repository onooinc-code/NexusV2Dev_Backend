<?php

namespace App\Jobs\PeopleConnect;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PeopleConnect\PeopleConnectMessage;
use App\Models\PeopleConnect\PeopleConnectReplyDraft;
use App\Services\PeopleConnect\PeopleConnectContextAssembler;
use App\Services\PeopleConnect\PeopleConnectAgentReplyService;
use App\Models\PeopleConnect\PeopleConnectProcessingLog;
use Throwable;

class GenerateContactReplyDraftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public PeopleConnectMessage $triggerMessage,
        public int $agentId
    ) {}

    public function handle(
        PeopleConnectContextAssembler $assembler,
        PeopleConnectAgentReplyService $agentReplyService
    ): void {
        $conversation = $this->triggerMessage->conversation;

        // 1. Assemble context
        $contextSnapshot = $assembler->assemble($conversation);

        // 2. Generate draft from AgentsHub
        $result = $agentReplyService->generateDraft($contextSnapshot, $this->agentId);

        // 3. Store draft
        PeopleConnectReplyDraft::create([
            'conversation_id' => $conversation->id,
            'contact_id' => $this->triggerMessage->contact_id,
            'context_snapshot_id' => $contextSnapshot->id,
            'trigger_message_id' => $this->triggerMessage->id,
            'body' => $result['body'],
            'agent_id' => $this->agentId,
            'status' => 'pending_approval',
            'trace_id' => $result['trace_id'],
        ]);

        // Phase 7 Realtime: broadcast reply.draft.created
    }

    public function failed(Throwable $exception): void
    {
        PeopleConnectProcessingLog::create([
            'conversation_id' => $this->triggerMessage->conversation_id,
            'event_type' => 'draft_generation_failed',
            'description' => $exception->getMessage(),
            'payload' => ['message_id' => $this->triggerMessage->id],
        ]);

        // Phase 7 Realtime: broadcast draft.failed
    }
}
