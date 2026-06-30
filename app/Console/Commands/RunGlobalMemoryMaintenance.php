<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\ContactMemoryMaintenanceRun;
use Illuminate\Console\Command;

class RunGlobalMemoryMaintenance extends Command
{
    protected $signature = 'nexus:memory-maintenance {--operation=prune_stale} {--dry-run}';
    protected $description = 'Dispatches memory maintenance jobs for all contacts';

    public function handle(): int
    {
        $operation = $this->option('operation');
        $isDryRun = $this->option('dry-run');

        $this->info("Starting global memory maintenance (Operation: {$operation})");

        $contacts = Contact::all();
        $dispatched = 0;

        foreach ($contacts as $contact) {
            $run = ContactMemoryMaintenanceRun::create([
                'operation' => $operation,
                'status' => 'queued',
                'scope' => ['contact_id' => $contact->id],
                'results' => ['dry_run' => $isDryRun]
            ]);

            \App\Jobs\RunContactMemoryMaintenanceJob::dispatch($run);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} memory maintenance jobs.");

        return self::SUCCESS;
    }
}
