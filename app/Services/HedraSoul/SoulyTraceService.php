<?php

namespace App\Services\HedraSoul;

use App\Models\SoulyActionTrace;
use Illuminate\Support\Str;

/**
 * SoulyTraceService: Records execution traces for every Souly action.
 * Provides audit trail with full trace_id, intent, action, model, tools, tasks, and cost.
 */
class SoulyTraceService
{
    /**
     * Record a Souly action trace with complete metadata.
     * Called after action execution to create audit record.
     */
    public function record(array $traceData): SoulyActionTrace
    {
        // Generate trace ID if not provided
        $traceId = $traceData['trace_id'] ?? Str::uuid()->toString();

        return SoulyActionTrace::create([
            'message_id' => $traceData['message_id'] ?? null,
            'trace_id' => $traceId,
            'parsed_intent' => $traceData['parsed_intent'] ?? null,
            'selected_action' => $traceData['selected_action'] ?? null,
            'model_instance_id' => $traceData['model_instance_id'] ?? null,
            'agent_id' => $traceData['agent_id'] ?? null,
            'instruction_version_id' => $traceData['instruction_version_id'] ?? null,
            'context_snapshot_id' => $traceData['context_snapshot_id'] ?? null,
            'tools_invoked' => $this->normalizeArray($traceData['tools_invoked'] ?? []),
            'tasks_created' => $this->normalizeArray($traceData['tasks_created'] ?? []),
            'workflows_triggered' => $this->normalizeArray($traceData['workflows_triggered'] ?? []),
            'approval_decision' => $traceData['approval_decision'] ?? null,
            'final_output' => $traceData['final_output'] ?? null,
            'cost_usd' => $traceData['cost_usd'] ?? 0.0,
            'duration_ms' => $traceData['duration_ms'] ?? 0,
            'errors' => $this->normalizeArray($traceData['errors'] ?? []),
        ]);
    }

    /**
     * Find a trace by trace ID.
     */
    public function findByTraceId(string $traceId): ?SoulyActionTrace
    {
        return SoulyActionTrace::where('trace_id', $traceId)->first();
    }

    /**
     * Get traces for a specific message.
     */
    public function getMessageTraces(int $messageId)
    {
        return SoulyActionTrace::where('message_id', $messageId)->get();
    }

    /**
     * Normalize array input (ensure it's an array, not JSON string).
     */
    protected function normalizeArray($value): array
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return is_array($value) ? $value : [];
    }
}
