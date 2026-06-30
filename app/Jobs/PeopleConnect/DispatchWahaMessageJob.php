<?php

namespace App\Jobs\PeopleConnect;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PeopleConnect\PeopleConnectMessage;
use App\Services\PeopleConnect\WahaMessageDispatcher;
use App\Models\PeopleConnect\PeopleConnectProcessingLog;
use Throwable;

class DispatchWahaMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public array $backoff = [30, 120, 300]; // 30s, 2min, 5min

    public function __construct(public PeopleConnectMessage $message) {}

    public function handle(WahaMessageDispatcher $dispatcher): void
    {
        $this->message->update(['status' => 'sending']);
        $dispatcher->send($this->message);
    }

    public function failed(Throwable $exception): void
    {
        $this->message->update(['status' => 'failed']);

        PeopleConnectProcessingLog::create([
            'conversation_id' => $this->message->conversation_id,
            'event_type' => 'message_dispatch_failed',
            'description' => $exception->getMessage(),
            'payload' => ['message_id' => $this->message->id],
        ]);

        // Phase 7 Realtime Broadcast: message.failed
    }
}
