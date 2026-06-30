<?php

namespace App\Jobs;

use App\Events\JobProgressUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWahaContactsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

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
        $jobId = 'sync_contacts_' . time();
        $totalItems = 250;
        $processed = 0;

        broadcast(new JobProgressUpdated($jobId, 'sync_contacts', 0, 0, $totalItems, 'running', 'Started contact synchronization'));

        // Simulate fetching batches
        for ($i = 1; $i <= 5; $i++) {
            sleep(1);
            $processed += 50;
            $progress = round(($processed / $totalItems) * 100);
            
            broadcast(new JobProgressUpdated($jobId, 'sync_contacts', $progress, $processed, $totalItems, 'running', "Fetched contacts batch $i/5..."));
            Log::info("Waha Sync Contacts batch $i processed.");
        }

        broadcast(new JobProgressUpdated($jobId, 'sync_contacts', 100, $totalItems, $totalItems, 'completed', 'Contact synchronization complete.'));
    }
}
