<?php

namespace App\Jobs;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EraseContactDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $contactId,
        public int $actorId
    ) {}

    public function handle(): void
    {
        $contact = Contact::find($this->contactId);
        if (!$contact) {
            return;
        }

        $contact->messages()->delete();
        $contact->memories()->delete();
        $contact->analysisFindings()->delete();

        event(new \App\Events\ContactDeleted($contact));

        $contact->delete();
        
        \Illuminate\Support\Facades\Log::info("Contact {$this->contactId} erased by {$this->actorId}");
    }
}
