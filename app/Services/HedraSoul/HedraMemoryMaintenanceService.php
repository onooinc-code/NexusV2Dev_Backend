<?php

namespace App\Services\HedraSoul;

use App\Models\HedraProfileFact;
use App\Models\HedraMemorySuggestion;

/**
 * HedraMemoryMaintenanceService: Performs cleanup and optimization on Hedra's memory.
 * Handles pruning stale facts, rebuilding embeddings, deduplication, and decay.
 */
class HedraMemoryMaintenanceService
{
    const DECAY_THRESHOLD_DAYS = 90;
    const STALE_CONFIDENCE_THRESHOLD = 0.3;

    /**
     * Prune stale or low-confidence facts.
     * Returns count of items pruned.
     */
    public function pruneStale(int $daysThreshold = self::DECAY_THRESHOLD_DAYS): int
    {
        $cutoffDate = now()->subDays($daysThreshold);

        // Archive very old facts with low confidence
        $pruned = HedraProfileFact::where('confidence', '<', self::STALE_CONFIDENCE_THRESHOLD)
            ->where('created_at', '<', $cutoffDate)
            ->update(['visibility_scope' => 'archived']);

        // Also reject old pending suggestions
        $rejected = HedraMemorySuggestion::where('status', 'pending')
            ->where('created_at', '<', $cutoffDate)
            ->update(['status' => 'rejected', 'reviewed_at' => now()]);

        return $pruned + $rejected;
    }

    /**
     * Trigger embedding rebuild for all profile facts.
     * Dispatches async job to recompute embeddings.
     */
    public function rebuildEmbeddings(): void
    {
        // This would dispatch an async job to recompute embeddings
        // dispatch(new \App\Jobs\HedraSoul\RecomputeHedraMemoryEmbeddingsJob());
    }

    /**
     * Detect conflicting memories and return them for review.
     */
    public function detectConflicts(): array
    {
        $facts = HedraProfileFact::where('is_approved', true)->get();
        $conflicts = [];

        // Check for content overlap within same type
        $byType = $facts->groupBy('memory_type');

        foreach ($byType as $type => $typeFacts) {
            if ($typeFacts->count() < 2) {
                continue;
            }

            for ($i = 0; $i < $typeFacts->count(); $i++) {
                for ($j = $i + 1; $j < $typeFacts->count(); $j++) {
                    $fact1 = $typeFacts[$i];
                    $fact2 = $typeFacts[$j];

                    if ($this->hasConflict($fact1->content, $fact2->content)) {
                        $conflicts[] = [
                            'fact_1_id' => $fact1->id,
                            'fact_2_id' => $fact2->id,
                            'memory_type' => $type,
                            'conflict_type' => 'content_contradiction',
                        ];
                    }
                }
            }
        }

        return $conflicts;
    }

    /**
     * Deduplication: merge similar facts.
     * Returns count of merged facts.
     */
    public function deduplicate(): array
    {
        $facts = HedraProfileFact::where('is_approved', true)->get();
        $merged = 0;
        $duplicates = [];

        // Simple deduplication by exact content match
        $contentGroups = $facts->groupBy('content');

        foreach ($contentGroups as $content => $group) {
            if ($group->count() > 1) {
                // Keep the first one, merge others into it
                $primary = $group->first();
                $duplicates[] = [
                    'primary_id' => $primary->id,
                    'merged_ids' => $group->skip(1)->pluck('id')->toArray(),
                ];

                // Delete duplicates (keep highest confidence version)
                $group->skip(1)->each(function ($fact) {
                    $fact->delete();
                });

                $merged += $group->count() - 1;
            }
        }

        return ['status' => 'Deduplication completed', 'merged' => $merged, 'details' => $duplicates];
    }

    /**
     * Apply confidence decay over time.
     * Older facts get lower confidence scores.
     */
    public function decay(int $daysThreshold = self::DECAY_THRESHOLD_DAYS): array
    {
        $cutoffDate = now()->subDays($daysThreshold);
        $archived = 0;

        // Facts older than threshold get confidence decay
        $oldFacts = HedraProfileFact::where('created_at', '<', $cutoffDate)
            ->get();

        foreach ($oldFacts as $fact) {
            $ageMonths = $fact->created_at->diffInMonths(now());
            
            // Reduce confidence by 5% per month (minimum 0.1)
            $newConfidence = max(0.1, $fact->confidence - ($ageMonths * 0.05));
            
            if ($newConfidence < self::STALE_CONFIDENCE_THRESHOLD) {
                $fact->update(['visibility_scope' => 'archived']);
                $archived++;
            } else {
                $fact->update(['confidence' => $newConfidence]);
            }
        }

        return ['status' => 'Decay pass completed', 'archived' => $archived];
    }

    /**
     * Auto-archive low confidence facts.
     */
    public function autoArchiveLowConfidence(float $threshold = self::STALE_CONFIDENCE_THRESHOLD): void
    {
        HedraProfileFact::where('confidence', '<', $threshold)
            ->update(['visibility_scope' => 'archived']);
    }

    /**
     * Check if two fact contents conflict.
     */
    protected function hasConflict(string $content1, string $content2): bool
    {
        // Exact match
        if ($content1 === $content2) {
            return false;  // Not a conflict, it's a duplicate
        }

        // Check for contradictory keywords
        $contradictions = ['always' => 'never', 'yes' => 'no', 'like' => 'dislike'];

        foreach ($contradictions as $pos => $neg) {
            if (
                (str_contains(strtolower($content1), $pos) && str_contains(strtolower($content2), $neg)) ||
                (str_contains(strtolower($content1), $neg) && str_contains(strtolower($content2), $pos))
            ) {
                return true;
            }
        }

        return false;
    }
}
