<?php

namespace App\Jobs\HedraSoul;

use App\Models\HedrasoulMessage;
use App\Services\AiModelsHub\AiModelsHubService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * AnalyzeHedraSoulMessageJob: Classifies message attributes.
 * 
 * Sends message body to AiModelsHub for NLP analysis, then updates:
 * - intent, topic, tone, sentiment columns on the message record
 */
class AnalyzeHedraSoulMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(public HedrasoulMessage $message) {}

    public function handle(): void
    {
        try {
            // Skip if message is already fully classified
            if ($this->message->intent && $this->message->topic && $this->message->tone && $this->message->sentiment) {
                return;
            }

            // Call AiModelsHub for classification
            // TODO: Replace with actual AiModelsHub implementation
            $analysis = $this->classifyMessage($this->message->body);

            // Update message with classification results
            $this->message->update([
                'intent' => $analysis['intent'] ?? $this->message->intent,
                'topic' => $analysis['topic'] ?? null,
                'tone' => $analysis['tone'] ?? null,
                'sentiment' => $analysis['sentiment'] ?? null,
            ]);

        } catch (Throwable $e) {
            $this->failed($e);
            throw $e;
        }
    }

    /**
     * Classify message using NLP analysis.
     * 
     * This is a placeholder - integrate with actual AiModelsHub classification service.
     */
    private function classifyMessage(string $body): array
    {
        // TODO: Implement actual classification via AiModelsHub
        return [
            'intent' => 'answer',
            'topic' => 'general',
            'tone' => 'neutral',
            'sentiment' => 'positive',
        ];
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $e): void
    {
        \Log::warning('AnalyzeHedraSoulMessageJob failed', [
            'message_id' => $this->message->id,
            'error' => $e->getMessage(),
        ]);

        // Don't create notification - this is a non-critical path
        // Message remains usable even without full classification
        $this->message->update([
            'intent' => $this->message->intent ?? 'unknown',
            'topic' => $this->message->topic ?? 'unclassified',
        ]);
    }
}
