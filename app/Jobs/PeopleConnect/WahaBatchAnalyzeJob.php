<?php

namespace App\Jobs\PeopleConnect;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WahaBatchAnalyzeJob implements ShouldQueue
{
    use Queueable;

    protected $processId;

    /**
     * Create a new job instance.
     */
    public function __construct($processId)
    {
        $this->processId = $processId;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\PeopleConnect\WahaAnalysisService $service): void
    {
        $process = \App\Models\WahaSyncProcess::find($this->processId);
        if (!$process || $process->status === 'paused') {
            return;
        }

        $service->analyzeContacts($process);
    }
}
