<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledTask;
use App\Jobs\ExecuteScheduledTask;
use Illuminate\Support\Facades\Log;

class RunScheduledTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nexus:run-scheduler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run due scheduled tasks from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for due scheduled tasks...');

        // In a real implementation, you'd parse cron expressions or check next_run_at
        $dueTasks = ScheduledTask::where('is_active', true)
            ->where(function($query) {
                $query->whereNull('next_run_at')
                      ->orWhere('next_run_at', '<=', now());
            })
            ->get();

        if ($dueTasks->isEmpty()) {
            $this->info('No tasks due at this time.');
            return;
        }

        foreach ($dueTasks as $task) {
            $this->info("Dispatching task: {$task->name}");
            
            // Dispatch to the queue
            ExecuteScheduledTask::dispatch($task);

            // Update next run time based on cron expression
            // This is a naive implementation placeholder
            $task->update([
                'last_run_at' => now(),
                // 'next_run_at' => (new CronExpression($task->cron_expression))->getNextRunDate()
            ]);
        }

        $this->info('Finished dispatching tasks.');
    }
}
