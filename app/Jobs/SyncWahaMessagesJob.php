<?php

namespace App\Jobs;

use App\Events\JobProgressUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWahaMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200; // 20 minutes

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobId = 'sync_messages_' . time();
        $totalItems = 1000; // Mock total for now
        $processed = 0;

        broadcast(new JobProgressUpdated($jobId, 'sync_messages', 0, 0, $totalItems, 'running', 'Started message synchronization'));

        // Simulate fetching batches
        for ($i = 1; $i <= 10; $i++) {
            // Mock API delay
            sleep(1);
            
            $processed += 100;
            $progress = round(($processed / $totalItems) * 100);
            
            broadcast(new JobProgressUpdated($jobId, 'sync_messages', $progress, $processed, $totalItems, 'running', "Fetched batch $i/10..."));
            Log::info("Waha Sync Message batch $i processed.");
        }

        broadcast(new JobProgressUpdated($jobId, 'sync_messages', 100, $totalItems, $totalItems, 'completed', 'Message synchronization complete.'));
    }
}
