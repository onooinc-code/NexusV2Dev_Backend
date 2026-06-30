<?php

namespace App\Jobs\HedraSoul;

use App\Models\HedrasoulMessage;
use App\Services\HedraSoul\HedraMemoryService;
use App\Services\HedraSoul\HedraSoulRealtimeBroadcaster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * CreateHedraMemorySuggestionJob: Creates memory suggestion from message.
 * 
 * Called after ProcessHedraSoulMessageJob completes.
 * Extracts memory-worthy content from message, creates pending suggestion record,
 * broadcasts memory.suggested event for frontend review.
 */
class CreateHedraMemorySuggestionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(public HedrasoulMessage $message) {}

    public function handle(): void
    {
        try {
            // Skip if this is a user message - only create suggestions from agent/system messages
            if ($this->message->sender_type === 'user') {
                return;
            }

            // Call HedraMemoryService to create suggestion
            $memoryService = app(HedraMemoryService::class);
            $suggestion = $memoryService->suggestFromMessage($this->message);

            if ($suggestion) {
                // Broadcast memory suggested event
                app(HedraSoulRealtimeBroadcaster::class)->broadcastMemorySuggested([
                    'suggestion_id' => $suggestion->id,
                    'memory_type' => $suggestion->memory_type,
                    'content_preview' => substr($suggestion->content, 0, 100),
                    'confidence' => $suggestion->confidence,
                    'message_id' => $this->message->id,
                ], $this->message->sender_id ?? auth()->id());
            }

        } catch (Throwable $e) {
            $this->failed($e);
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $e): void
    {
        \Log::warning('CreateHedraMemorySuggestionJob failed', [
            'message_id' => $this->message->id,
            'error' => $e->getMessage(),
        ]);

        // This is a non-critical path - don't disrupt user experience
        // Memory suggestions failing should not affect the main message processing flow
    }
}
