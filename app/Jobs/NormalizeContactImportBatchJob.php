<?php

namespace App\Jobs;

use App\Models\ContactImportBatch;
use App\Models\ContactAuditEvent;
use Throwable;
use Illuminate\Support\Facades\DB;

class NormalizeContactImportBatchJob extends BaseJob
{
    public $queue = 'contacts';

    public function __construct(
        public ContactImportBatch $batch
    ) {}

    public function handle(): void
    {
        DB::statement("
            DELETE t1 FROM contact_messages t1
            INNER JOIN contact_messages t2 
            WHERE 
                t1.id > t2.id AND 
                t1.contact_import_batch_id = ? AND 
                t2.contact_import_batch_id = ? AND
                ((t1.source_message_id IS NOT NULL AND t1.source_message_id = t2.source_message_id)
                 OR 
                 (t1.content_hash = t2.content_hash AND t1.timestamp = t2.timestamp))
        ", [$this->batch->id, $this->batch->id]);
    }

    public function failed(Throwable $exception): void
    {
        ContactAuditEvent::create([
            'contact_id' => $this->batch->contact_id,
            'action' => 'normalize_failed',
            'description' => "Normalize batch {$this->batch->id} failed: " . $exception->getMessage()
        ]);
    }
}
