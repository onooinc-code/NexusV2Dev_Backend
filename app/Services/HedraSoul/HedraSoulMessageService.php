<?php

namespace App\Services\HedraSoul;

use App\Models\HedrasoulMessage;
use App\Models\HedrasoulSession;
use App\Jobs\HedraSoul\ProcessHedraSoulMessageJob;

class HedraSoulMessageService
{
    /**
     * Store a message with explicit parameters (used by tests and controllers).
     *
     * @param HedrasoulSession $session
     * @param string $body
     * @param string $senderType 'user' | 'assistant' | 'system'
     * @param array  $metadata   Optional extra data stored in metadata column
     */
    public function storeMessage(HedrasoulSession $session, string $body, string $senderType = 'user', array $metadata = []): HedrasoulMessage
    {
        return $session->messages()->create([
            'sender_type'  => $senderType,
            'sender_id'    => auth()->id(),
            'body'         => $body,
            'body_format'  => 'markdown',
            'status'       => 'received',
            'intent'       => null,
            'topic'        => null,
            'tone'         => null,
            'sentiment'    => null,
            'risk_level'   => null,
            'is_streaming' => false,
            'token_count'  => 0,
            'cost_usd'     => 0,
        ]);
    }

    /**
     * Save a new HedraSoul message and dispatch async processing job.
     * Returns HTTP 202 immediately from controller.
     */
    public function save(array $data, HedrasoulSession $session): HedrasoulMessage
    {
        $message = $session->messages()->create([
            'sender_type' => $data['sender_type'] ?? 'user',
            'sender_id' => $data['sender_id'] ?? auth()->id(),
            'body' => $data['body'],
            'body_format' => $data['body_format'] ?? 'markdown',
            'status' => 'pending',
            'intent' => null,  // Will be set by ProcessHedraSoulMessageJob
            'topic' => null,   // Will be set by AnalyzeHedraSoulMessageJob
            'tone' => null,    // Will be set by AnalyzeHedraSoulMessageJob
            'sentiment' => null, // Will be set by AnalyzeHedraSoulMessageJob
            'risk_level' => null, // Will be set by ProcessHedraSoulMessageJob
            'is_streaming' => false,
            'token_count' => 0,
            'cost_usd' => 0,
        ]);

        // Dispatch async job to process the message
        ProcessHedraSoulMessageJob::dispatch($message);

        return $message;
    }

    /**
     * Update message fields after processing (used by jobs).
     */
    public function updateMessage(HedrasoulMessage $message, array $data): HedrasoulMessage
    {
        $message->update($data);
        return $message;
    }

    /**
     * Mark a message as streaming (real-time response indicator).
     */
    public function markAsStreaming(HedrasoulMessage $message, bool $isStreaming): void
    {
        $message->update(['is_streaming' => $isStreaming]);
    }

    /**
     * Get messages for a session with optional pagination.
     */
    public function getSessionMessages(HedrasoulSession $session, int $limit = 50)
    {
        return $session->messages()
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }
}
