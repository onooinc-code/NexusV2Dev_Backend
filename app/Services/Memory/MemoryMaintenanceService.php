<?php

namespace App\Services\Memory;

use App\Services\AiModelsHub\UniversalAiGatewayService;
use App\Services\LogService;
use App\Jobs\VectorizeMemoryJob;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * MemoryMaintenanceService
 *
 * Handles consolidation, decay, and pruning of memory records.
 * Uses AiModelsHub to intelligently identify and resolve memory conflicts.
 * Req: 8.1, 8.2, 8.3, 8.6, 8.7
 */
class MemoryMaintenanceService
{
    public function __construct(
        protected UniversalAiGatewayService $aiGateway,
        protected StructuredMemoryService $structuredMemoryService,
        protected LogService $logService,
    ) {}

    /**
     * Run full consolidation pipeline for a contact's structured memories.
     * Identifies duplicates and contradictions, then merges or supersedes as appropriate.
     * Only processes records updated more than 24 hours ago (Req 8.7).
     *
     * @param int $contactId
     * @return array Results including 'merged' and 'superseded' counts
     */
    public function runConsolidation(int $contactId): array
    {
        // 1. Load all active structured memories for the contact, excluding recently updated ones
        $memories = DB::table('structured_memories')
            ->where('contact_id', $contactId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->where('updated_at', '<', now()->subHours(24))
            ->get();

        if ($memories->count() < 2) {
            return ['merged' => 0, 'superseded' => 0];
        }

        // 2. Ask AiModelsHub to identify duplicate / contradictory pairs
        $pairs = $this->identifyMemoryConflicts($memories->toArray());

        $merged = 0;
        $superseded = 0;

        // 3. Process pairs in a transaction
        DB::transaction(function () use ($pairs, &$merged, &$superseded) {
            foreach ($pairs as $pair) {
                if ($pair['relationship'] === 'duplicate') {
                    $this->mergeRecords($pair['keep_id'], $pair['remove_id']);
                    $merged++;

                    $this->logService->info('Memory records merged', [
                        'contact_id' => $pair['contact_id'] ?? null,
                        'kept_record_id' => $pair['keep_id'],
                        'removed_record_id' => $pair['remove_id'],
                        'reason' => 'duplicate',
                    ]);
                } elseif ($pair['relationship'] === 'contradictory') {
                    $this->supersede($pair['keep_id'], $pair['remove_id']);
                    $superseded++;

                    $this->logService->info('Memory record superseded', [
                        'contact_id' => $pair['contact_id'] ?? null,
                        'kept_record_id' => $pair['keep_id'],
                        'superseded_record_id' => $pair['remove_id'],
                        'reason' => 'contradictory',
                    ]);
                }
            }
        });

        $this->logService->info('Memory consolidation completed', [
            'contact_id' => $contactId,
            'merged_count' => $merged,
            'superseded_count' => $superseded,
        ]);

        return compact('merged', 'superseded');
    }

    /**
     * Call AiModelsHub to identify memory conflicts.
     * Returns an array of conflict pairs with relationship type and which to keep/remove.
     *
     * @param array $memories Array of memory records from structured_memories table
     * @return array Array of conflict pairs with format:
     *               [
     *                   'keep_id' => int,
     *                   'remove_id' => int,
     *                   'relationship' => 'duplicate|contradictory',
     *                   'contact_id' => int
     *               ]
     */
    protected function identifyMemoryConflicts(array $memories): array
    {
        // Format memories for the AI gateway
        $formattedMemories = array_map(fn($mem) => [
            'id' => $mem->id,
            'fact_type' => $mem->fact_type,
            'data' => is_string($mem->data) ? json_decode($mem->data, true) : $mem->data,
            'confidence' => $mem->confidence,
            'updated_at' => $mem->updated_at,
            'contact_id' => $mem->contact_id,
        ], $memories);

        try {
            // Call the AI gateway to analyze for conflicts
            // This would use a specialized prompt to identify semantic duplicates/contradictions
            $result = $this->aiGateway->executeWithAgent(
                app(\App\Models\Agent::class),
                [
                    'input' => json_encode($formattedMemories),
                    'system_prompt' => $this->getConflictAnalysisPrompt(),
                ]
            );

            if (!$result['success'] || !isset($result['output'])) {
                $this->logService->warning('AiModelsHub conflict identification failed', [
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
                return [];
            }

            // Parse the JSON response from the AI
            $pairs = json_decode($result['output'], true);
            
            if (!is_array($pairs)) {
                $this->logService->warning('Invalid conflict identification response format');
                return [];
            }

            return $pairs;
        } catch (\Exception $e) {
            $this->logService->error('Memory conflict identification exception', [
                'error' => $e->getMessage(),
                'memory_count' => count($formattedMemories),
            ]);
            return [];
        }
    }

    /**
     * Get the system prompt for conflict analysis.
     *
     * @return string
     */
    protected function getConflictAnalysisPrompt(): string
    {
        return <<<'PROMPT'
You are analyzing structured memory records for semantic conflicts.
Identify pairs of records that are:
1. DUPLICATES: Same fact expressed differently (merge keeping higher confidence)
2. CONTRADICTORY: Conflicting information about the same fact (keep more recent)

Return a JSON array of conflicts. Each item should have:
{
  "keep_id": <record_id of the one to keep>,
  "remove_id": <record_id to remove/merge>,
  "relationship": "duplicate" or "contradictory",
  "reason": "brief explanation",
  "confidence": <your confidence 0-1>
}

Only include conflicts with confidence > 0.7. Return empty array if no conflicts found.
PROMPT;
    }

    /**
     * Merge two structured memory records.
     * Combines data, retains higher confidence, soft-deletes the redundant record,
     * and dispatches VectorizeMemoryJob for the kept record.
     *
     * @param int $keepId ID of record to keep
     * @param int $removeId ID of record to remove
     * @return void
     */
    private function mergeRecords(int $keepId, int $removeId): void
    {
        $keep = DB::table('structured_memories')->find($keepId);
        $remove = DB::table('structured_memories')->find($removeId);

        if (!$keep || !$remove) {
            return;
        }

        // Merge data: combine both data objects, with kept record taking precedence
        $keepData = is_string($keep->data) ? json_decode($keep->data, true) : $keep->data;
        $removeData = is_string($remove->data) ? json_decode($remove->data, true) : $remove->data;

        $mergedData = array_merge($removeData ?? [], $keepData ?? []);

        // Retain higher confidence
        $betterConf = max($keep->confidence, $remove->confidence);

        // Update kept record
        DB::table('structured_memories')->where('id', $keepId)->update([
            'data' => json_encode($mergedData),
            'confidence' => $betterConf,
            'metadata' => json_encode(array_merge(
                json_decode($keep->metadata ?? '{}', true),
                ['merged_from' => $removeId, 'merged_at' => now()->toDateTimeString()]
            )),
            'updated_at' => now(),
        ]);

        // Soft-delete the removed record and mark it as merged
        DB::table('structured_memories')->where('id', $removeId)->update([
            'deleted_at' => now(),
            'metadata' => json_encode(array_merge(
                json_decode($remove->metadata ?? '{}', true),
                ['merged_into' => $keepId]
            )),
        ]);

        // Record version entry for the kept record
        $keepContentBefore = $keepData;
        $this->structuredMemoryService->recordVersion(
            $keepId,
            $keep->confidence,
            $betterConf,
            'consolidation_merge',
            $keepContentBefore,
            $mergedData
        );

        // Dispatch re-indexing job for kept record
        VectorizeMemoryJob::dispatch($keepId, json_encode($mergedData));
    }

    /**
     * Supersede an older record with a newer one.
     * Marks the older record as 'superseded' in metadata and soft-deletes it.
     *
     * @param int $keepId ID of newer/correct record to keep
     * @param int $removeId ID of older/incorrect record to supersede
     * @return void
     */
    private function supersede(int $keepId, int $removeId): void
    {
        $keep = DB::table('structured_memories')->find($keepId);
        $remove = DB::table('structured_memories')->find($removeId);

        if (!$keep || !$remove) {
            return;
        }

        // Mark the older record as superseded and soft-delete it
        DB::table('structured_memories')->where('id', $removeId)->update([
            'deleted_at' => now(),
            'metadata' => json_encode(array_merge(
                json_decode($remove->metadata ?? '{}', true),
                [
                    'superseded_by' => $keepId,
                    'superseded_at' => now()->toDateTimeString(),
                    'reason' => 'contradictory_information',
                ]
            )),
        ]);

        // Record version entry for supersedence
        $removeData = is_string($remove->data) ? json_decode($remove->data, true) : $remove->data;
        $this->structuredMemoryService->recordVersion(
            $removeId,
            $remove->confidence,
            null,
            'consolidation_supersede',
            $removeData,
            null
        );
    }

    /**
     * Apply time-decay to structured memories not reinforced recently.
     * Calls StructuredMemoryService::applyDecay() and returns count of affected records.
     *
     * @return int Number of records affected by decay
     */
    public function runDecay(): int
    {
        return $this->structuredMemoryService->applyDecay();
    }
}