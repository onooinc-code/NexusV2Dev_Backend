<?php

namespace App\Events;

use App\Models\Contact;
use App\Models\ContactImportBatch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactImportStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Contact $contact,
        public ContactImportBatch $batch
    ) {}
}
