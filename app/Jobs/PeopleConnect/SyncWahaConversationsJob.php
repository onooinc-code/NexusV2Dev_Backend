<?php

namespace App\Jobs\PeopleConnect;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\PeopleConnect\LiveMsgsSyncService;
use Throwable;

class SyncWahaConversationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(LiveMsgsSyncService $syncService): void
    {
        $syncService->syncConversations();
    }
    
    public function failed(Throwable $exception): void
    {
        \Log::error('SyncWahaConversationsJob failed: ' . $exception->getMessage());
    }
}
