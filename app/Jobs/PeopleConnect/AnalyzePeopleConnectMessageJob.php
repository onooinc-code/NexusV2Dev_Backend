<?php

namespace App\Jobs\PeopleConnect;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PeopleConnect\PeopleConnectMessage;
use App\Services\PeopleConnect\PeopleConnectAnalysisService;
use App\Services\PeopleConnect\PeopleConnectRealtimeBroadcaster;
use App\Models\PeopleConnect\PeopleConnectProcessingLog;
use Throwable;

class AnalyzePeopleConnectMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(public PeopleConnectMessage $message) {}

    public function handle(
        PeopleConnectAnalysisService $analysisService,
        PeopleConnectRealtimeBroadcaster $broadcaster
    ): void {
        $analysis = $analysisService->analyze($this->message);

        // Broadcast message.analyzed event
        $broadcaster->messageAnalyzed($this->message, $analysis);
    }

    public function failed(Throwable $exception): void
    {
        PeopleConnectProcessingLog::create([
            'conversation_id' => $this->message->conversation_id,
            'event_type' => 'analysis_failed',
            'description' => $exception->getMessage(),
            'payload' => ['message_id' => $this->message->id],
        ]);
    }
}

