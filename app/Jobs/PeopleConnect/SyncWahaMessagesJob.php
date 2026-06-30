<?php

namespace App\Jobs\PeopleConnect;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\PeopleConnect\LiveMsgsSyncService;
use Throwable;

class SyncWahaMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $processId;

    public function __construct($processId = null)
    {
        $this->processId = $processId;
    }

    public function handle(LiveMsgsSyncService $syncService): void
    {
        $syncService->syncMessages($this->processId);
    }
    
    public function failed(Throwable $exception): void
    {
        \Log::error('SyncWahaMessagesJob failed: ' . $exception->getMessage());
    }
}
