<?php

namespace App\Services\HedraSoul;

use App\Models\HedraCloneSource;
use Illuminate\Support\Collection;

/**
 * HedraCloneProfileService: Manages clone sources that shape Souly's behavior.
 * Provides CRUD operations and conflict detection for sources.
 */
class HedraCloneProfileService
{
    /**
     * Create a new clone source.
     */
    public function create(array $data): HedraCloneSource
    {
        $data['is_archived'] = false;
        $data['validation_status'] = 'pending';

        return HedraCloneSource::create($data);
    }

    /**
     * Update a clone source.
     */
    public function update(HedraCloneSource $source, array $data): HedraCloneSource
    {
        $source->update($data);
        return $source;
    }

    /**
     * Archive a clone source (soft delete).
     */
    public function archive(HedraCloneSource $source): void
    {
        $source->update(['is_archived' => true]);
    }

    /**
     * Hard delete a clone source.
     */
    public function delete(HedraCloneSource $source): void
    {
        $source->forceDelete();
    }

    /**
     * Restore an archived clone source.
     */
    public function restore(HedraCloneSource $source): void
    {
        $source->update(['is_archived' => false]);
    }

    /**
     * Get all active clone sources grouped by source type.
     */
    public function getActiveByType()
    {
        return HedraCloneSource::active()
            ->get()
            ->groupBy('source_type');
    }

    /**
     * Detect conflicts between clone sources.
     * Returns array of conflicting source pairs.
     */
    public function detectConflicts(): array
    {
        $sources = HedraCloneSource::active()->get();
        $conflicts = [];

        // Group by source_type to find conflicts within same type
        $byType = $sources->groupBy('source_type');

        foreach ($byType as $type => $typeSources) {
            if ($typeSources->count() < 2) {
                continue;
            }

            // Check each pair for content similarity (simple substring match)
            for ($i = 0; $i < $typeSources->count(); $i++) {
                for ($j = $i + 1; $j < $typeSources->count(); $j++) {
                    $source1 = $typeSources[$i];
                    $source2 = $typeSources[$j];

                    // Detect conflicts by substring and semantic similarity
                    if ($this->hasConflict($source1->content, $source2->content)) {
                        $conflicts[] = [
                            'source_1_id' => $source1->id,
                            'source_1_type' => $type,
                            'source_2_id' => $source2->id,
                            'source_2_type' => $type,
                            'conflict_type' => 'content_overlap',
                            'severity' => $this->calculateConflictSeverity($source1, $source2),
                        ];
                    }
                }
            }
        }

        return $conflicts;
    }

    /**
     * Check if two source contents have conflict.
     */
    protected function hasConflict(string $content1, string $content2): bool
    {
        // Simple heuristic: check for substring overlap (could be enhanced with NLP)
        $words1 = array_filter(str_word_count($content1, 1));
        $words2 = array_filter(str_word_count($content2, 1));

        $intersection = array_intersect($words1, $words2);
        $overlapRatio = count($intersection) / min(count($words1), count($words2));

        // Conflict if > 60% word overlap
        return $overlapRatio > 0.6;
    }

    /**
     * Calculate conflict severity (low/medium/high).
     */
    protected function calculateConflictSeverity(HedraCloneSource $s1, HedraCloneSource $s2): string
    {
        // High severity if both are high confidence and sensitive
        if ($s1->confidence > 0.8 && $s2->confidence > 0.8 && $s1->sensitivity === 'sensitive') {
            return 'high';
        }

        // Medium severity if medium confidence or different sensitivity
        if ($s1->confidence > 0.6 && $s2->confidence > 0.6) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get single clone source by ID.
     */
    public function getSource(int $id): ?HedraCloneSource
    {
        return HedraCloneSource::find($id);
    }

    /**
     * Get all clone sources (active and archived).
     */
    public function getSources($limit = 50)
    {
        return HedraCloneSource::orderBy('created_at', 'desc')->paginate($limit);
    }
}
