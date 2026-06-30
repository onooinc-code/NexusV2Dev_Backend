<?php

namespace App\Jobs;

use App\Models\ContactImportBatch;
use App\Services\Contact\ContactImportRollbackService;
use App\Models\ContactAuditEvent;
use Throwable;

class RollbackContactImportBatchJob extends BaseJob
{
    public $queue = 'contacts';

    public function __construct(
        public ContactImportBatch $batch
    ) {}

    public function handle(ContactImportRollbackService $service): void
    {
        $service->rollback($this->batch);
    }

    public function failed(Throwable $exception): void
    {
        ContactAuditEvent::create([
            'contact_id' => $this->batch->contact_id,
            'action' => 'rollback_failed',
            'description' => "Rollback batch {$this->batch->id} failed: " . $exception->getMessage()
        ]);
    }
}
