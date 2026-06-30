<?php

namespace App\Events;

use App\Models\Contact;
use App\Models\ContactMemoryMaintenanceRun;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactMemoryMaintenanceCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ContactMemoryMaintenanceRun $run,
        public ?Contact $contact
    ) {}
}
