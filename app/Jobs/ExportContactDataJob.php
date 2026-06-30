<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\ContactAuditEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ZipArchive;

class ExportContactDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function __construct(
        public Contact $contact,
        public string $requesterEmail,
        public int $actorId
    ) {}

    public function handle(): void
    {
        $data = [
            'profile' => $this->contact->toArray(),
            'messages' => $this->contact->messages()->get()->toArray(),
            'memories' => $this->contact->memories()->get()->toArray(),
            'findings' => $this->contact->analysisFindings()->get()->toArray(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT);
        
        $filename = "export_contact_{$this->contact->id}_" . time() . ".zip";
        $path = storage_path("app/public/{$filename}");
        
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE) === TRUE) {
            $zip->addFromString('contact_data.json', $json);
            $zip->close();
        }

        ContactAuditEvent::create([
            'contact_id' => $this->contact->id,
            'action' => 'data_exported',
            'actor_type' => 'user',
            'actor_id' => $this->actorId,
            'description' => "Export generated for " . $this->requesterEmail
        ]);
    }
}
