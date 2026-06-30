<?php

namespace App\Services\HedraSoul;

use App\Models\HedrasoulSession;
use App\Models\HedrasoulMessage;
use App\Models\HedrasoulContextSnapshot;
use App\Models\HedraProfileFact;

/**
 * SoulyContextAssembler: Builds a complete context snapshot for message processing.
 * Collects instruction, persona, session history, mentions, facts, and computes token estimate.
 */
class SoulyContextAssembler
{
    const TOKEN_BUDGET = 8000;  // Default context token budget
    const RECENT_MESSAGES_LIMIT = 20;

    /**
     * Assemble complete context snapshot for a message.
     * Returns a persisted HedrasoulContextSnapshot record.
     */
    public function assemble(HedrasoulSession $session, HedrasoulMessage $trigger): HedrasoulContextSnapshot
    {
        $profile = app(SoulyRuntimeProfileService::class)->getCurrent();

        // Collect context components
        $instructionContent = $profile->activeInstructionVersion?->content ?? [];
        $persona = $profile->activePersona?->name ?? 'Default Assistant';
        $sessionSummary = $session->summary ?? '';

        // Get recent messages from session (chronologically ordered)
        $recentMessages = $session->messages()
            ->orderBy('created_at', 'desc')
            ->take(self::RECENT_MESSAGES_LIMIT)
            ->get()
            ->reverse()
            ->map(fn($msg) => [
                'id' => $msg->id,
                'sender_type' => $msg->sender_type,
                'body' => $msg->body,
                'created_at' => $msg->created_at->toIso8601String(),
            ])
            ->toArray();

        // Get mentions from trigger message
        $mentions = $trigger->mentions()
            ->with(['relatedObject' => function ($q) { 
                // Polymorphic eager load would go here
            }])
            ->get()
            ->map(fn($m) => [
                'type' => $m->mention_type,
                'display_name' => $m->display_name,
                'sensitivity' => $m->sensitivity,
                'resolved' => !is_null($m->resolved_at),
            ])
            ->toArray();

        // Get injected profile facts (active, non-archived)
        $injectedFacts = HedraProfileFact::where('is_approved', true)
            ->whereNotIn('visibility_scope', ['archived'])
            ->get()
            ->map(fn($f) => [
                'type' => $f->memory_type,
                'content' => $f->content,
                'confidence' => $f->confidence,
            ])
            ->toArray();

        // Build payload
        $payload = [
            'instruction_version_id' => $profile->active_instruction_version_id,
            'instruction_content' => $instructionContent,
            'persona' => $persona,
            'session_summary' => $sessionSummary,
            'recent_messages' => $recentMessages,
            'mentions' => $mentions,
            'injected_facts' => $injectedFacts,
            'tool_permissions' => $profile->tool_permissions ?? [],
            'memory_access' => $profile->memory_access,
            'contact_access' => $profile->contact_access,
            'task_execution_access' => $profile->task_execution_access,
            'workflow_execution_access' => $profile->workflow_execution_access,
            'external_messaging_access' => $profile->external_messaging_access,
        ];

        // Estimate tokens (rough: 1 token ≈ 4 chars)
        $jsonPayload = json_encode($payload);
        $tokenEstimate = strlen($jsonPayload) / 4;
        $excludedItems = [];

        // Truncate oldest messages if over budget
        if ($tokenEstimate > self::TOKEN_BUDGET) {
            $payload['recent_messages'] = array_slice($payload['recent_messages'], -10);
            $excludedItems[] = [
                'key' => 'older_messages',
                'reason' => 'Exceeded token budget (limit: ' . self::TOKEN_BUDGET . ')',
            ];

            // Recalculate after truncation
            $jsonPayload = json_encode($payload);
            $tokenEstimate = strlen($jsonPayload) / 4;
        }

        // Determine risk classification
        $riskClassification = $this->classifyRisk($payload);

        // Persist snapshot
        return HedrasoulContextSnapshot::create([
            'session_id' => $session->id,
            'message_id' => $trigger->id,
            'instruction_version_id' => $profile->active_instruction_version_id,
            'persona_id' => $profile->active_persona_id,
            'model_instance_id' => $profile->active_model_instance_id,
            'payload' => $payload,
            'token_estimate' => $tokenEstimate,
            'risk_classification' => $riskClassification,
            'excluded_items' => $excludedItems,
        ]);
    }

    /**
     * Classify risk level of assembled context based on included elements.
     */
    protected function classifyRisk(array $payload): string
    {
        $risk = 'low';

        if (!empty($payload['injected_facts'])) {
            $sensitiveItems = array_filter($payload['injected_facts'], 
                fn($f) => in_array($f['type'], ['boundary', 'decision', 'correction']));
            if (!empty($sensitiveItems)) {
                $risk = 'medium';
            }
        }

        if ($payload['external_messaging_access'] && !empty($payload['mentions'])) {
            $risk = 'high';
        }

        return $risk;
    }
}
