<?php

namespace App\Jobs\PeopleConnect;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\PeopleConnect\LiveMsgsSyncService;
use Throwable;

class SyncSingleContactMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contactId;
    protected $processId;

    public function __construct($contactId, $processId = null)
    {
        $this->contactId = $contactId;
        $this->processId = $processId;
    }

    public function handle(LiveMsgsSyncService $syncService): void
    {
        $syncService->syncSingleContactMessages($this->contactId, $this->processId);
    }
    
    public function failed(Throwable $exception): void
    {
        \Log::error('SyncSingleContactMessagesJob failed: ' . $exception->getMessage());
    }
}
