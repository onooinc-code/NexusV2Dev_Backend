<?php

namespace App\Services\HedraSoul;

use App\Models\HedraMemorySuggestion;
use App\Models\HedraProfileFact;
use App\Models\HedraMemoryVersion;
use App\Models\HedrasoulMessage;

/**
 * HedraMemoryService: Manages Hedra's profile facts and memory suggestions.
 * Provides lifecycle for suggesting, approving, rejecting, and searching memories.
 */
class HedraMemoryService
{
    /**
     * Create a memory suggestion from a message.
     * Status = pending until reviewed.
     */
    public function suggestFromMessage(HedrasoulMessage $msg): HedraMemorySuggestion
    {
        // Extract memory-related content from message
        $content = $this->extractMemoryContent($msg->body);

        return HedraMemorySuggestion::create([
            'source_message_id' => $msg->id,
            'content' => $content,
            'memory_type' => $this->classifyMemoryType($msg->body),
            'confidence' => 0.75,  // Default confidence
            'status' => 'pending',
        ]);
    }

    /**
     * Approve a memory suggestion and create corresponding profile fact.
     * Sets suggestion status = approved and reviewed_at = now.
     */
    public function approve(HedraMemorySuggestion $sug): HedraProfileFact
    {
        $sug->update([
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        // Create corresponding profile fact
        $fact = HedraProfileFact::create([
            'memory_type' => $sug->memory_type,
            'content' => $sug->content,
            'confidence' => $sug->confidence,
            'sensitivity' => 'internal',
            'visibility_scope' => 'private',
            'is_approved' => true,
            'approved_at' => now(),
            'version' => 1,
        ]);

        // Create initial version record
        HedraMemoryVersion::create([
            'fact_id' => $fact->id,
            'content' => $sug->content,
            'version_number' => 1,
            'changed_by' => auth()->id(),
            'change_reason' => 'Initial creation from suggestion',
        ]);

        // Broadcast memory approved event
        app(HedraSoulRealtimeBroadcaster::class)->broadcastMemoryApproved([
            'fact_id' => $fact->id,
            'suggestion_id' => $sug->id,
            'memory_type' => $sug->memory_type,
        ], auth()->id());

        return $fact;
    }

    /**
     * Reject a memory suggestion (fact will not be created).
     */
    public function reject(HedraMemorySuggestion $sug): void
    {
        $sug->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Create a profile fact directly.
     */
    public function createFact(array $data): HedraProfileFact
    {
        $data['is_approved'] = true;
        $data['approved_at'] = now();
        $data['version'] = 1;

        $fact = HedraProfileFact::create($data);

        // Create version record
        HedraMemoryVersion::create([
            'fact_id' => $fact->id,
            'content' => $data['content'],
            'version_number' => 1,
            'changed_by' => auth()->id(),
            'change_reason' => $data['change_reason'] ?? 'Created',
        ]);

        return $fact;
    }

    /**
     * Update a profile fact and create version record.
     */
    public function updateFact(HedraProfileFact $fact, array $data): HedraProfileFact
    {
        // Create new version record before updating
        $newVersion = $fact->version + 1;
        HedraMemoryVersion::create([
            'fact_id' => $fact->id,
            'content' => $data['content'] ?? $fact->content,
            'version_number' => $newVersion,
            'changed_by' => auth()->id(),
            'change_reason' => $data['change_reason'] ?? 'Updated',
        ]);

        // Update fact
        $data['version'] = $newVersion;
        $fact->update($data);

        return $fact;
    }

    /**
     * Delete a profile fact.
     */
    public function deleteFact(HedraProfileFact $fact): void
    {
        $fact->delete();
    }

    /**
     * Search profile facts by content.
     */
    public function search(string $query): array
    {
        return HedraProfileFact::where('is_approved', true)
            ->where(function ($q) use ($query) {
                $q->where('content', 'LIKE', "%{$query}%")
                  ->orWhere('memory_type', 'LIKE', "%{$query}%");
            })
            ->get()
            ->toArray();
    }

    /**
     * Get pending suggestions.
     */
    public function getPendingSuggestions($limit = 50)
    {
        return HedraMemorySuggestion::pending()
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Get all profile facts with optional filtering.
     */
    public function getFacts(?string $type = null, $limit = 50)
    {
        $query = HedraProfileFact::where('is_approved', true);

        if ($type) {
            $query->where('memory_type', $type);
        }

        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Extract memory content from message text.
     */
    protected function extractMemoryContent(string $body): string
    {
        // Simple extraction: take first 200 chars
        return substr($body, 0, 200);
    }

    /**
     * Classify memory type based on message content.
     */
    protected function classifyMemoryType(string $body): string
    {
        $body = strtolower($body);

        if (preg_match('/prefer|like|dislike|enjoy/i', $body)) {
            return 'preference';
        } elseif (preg_match('/value|important|care|principle/i', $body)) {
            return 'semantic';
        } elseif (preg_match('/boundary|limit|avoid|never/i', $body)) {
            return 'boundary';
        } elseif (preg_match('/voice|tone|style|write|speak/i', $body)) {
            return 'tone_style';
        } else {
            return 'episodic';
        }
    }
}
