<?php

namespace App\Jobs;

use App\Models\ScheduledTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteScheduledTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $task;

    /**
     * Create a new job instance.
     */
    public function __construct(ScheduledTask $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Executing scheduled task: {$this->task->name}");
        
        try {
            // Simulated execution logic depending on task type
            // e.g., if ($this->task->type === 'agent_run') ...

            Log::info("Task completed successfully: {$this->task->name}");
        } catch (\Exception $e) {
            Log::error("Scheduled task failed: {$this->task->name}. Error: {$e->getMessage()}");
            throw $e;
        }
    }
}
