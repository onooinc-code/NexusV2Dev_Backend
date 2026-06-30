<?php

namespace App\Jobs;

use App\Models\WorkflowExecution;
use App\Services\WorkflowExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteWorkflowStep implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $execution;
    public $nodeId;

    /**
     * Create a new job instance.
     */
    public function __construct(WorkflowExecution $execution, string $nodeId)
    {
        $this->execution = $execution;
        $this->nodeId = $nodeId;
    }

    /**
     * Execute the job.
     */
    public function handle(WorkflowExecutionService $service): void
    {
        $service->executeNode($this->execution, $this->nodeId);
    }
}
