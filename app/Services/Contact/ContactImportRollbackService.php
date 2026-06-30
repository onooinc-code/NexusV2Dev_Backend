<?php

namespace App\Services\Contact;

use App\Models\ContactImportBatch;
use App\Events\ContactImportCompleted;
use Illuminate\Support\Facades\DB;

class ContactImportRollbackService
{
    public function rollback(ContactImportBatch $batch): array
    {
        return DB::transaction(function () use ($batch) {
            try {
                // Count messages to delete
                $messageCount = $batch->messages()->count();

                // Delete all messages from this batch
                $batch->messages()->delete();

                // Mark batch as rolled back
                $batch->update(['status' => 'rolled_back']);
                
                // Dispatch event
                event(new ContactImportCompleted($batch->contact, 0, $batch->source, 'rolled_back'));

                return [
                    'success' => true,
                    'batch_id' => $batch->id,
                    'deleted' => $messageCount,
                    'message' => "Rolled back {$messageCount} messages",
                ];

            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        });
    }
}
