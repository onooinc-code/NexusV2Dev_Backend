<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ContactMessage;
use App\Models\Contact;
use App\Models\WahaSyncProcess;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessWahaMessageChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    protected $contactId;
    protected $chunk;
    protected $processId;

    public function __construct($contactId, array $chunk, $processId = null)
    {
        $this->contactId = $contactId;
        $this->chunk = $chunk;
        $this->processId = $processId;
    }

    public function handle(): void
    {
        $contact = Contact::find($this->contactId);
        if (!$contact) {
            return;
        }

        $process = $this->processId ? WahaSyncProcess::find($this->processId) : null;

        if ($process && $process->status === 'paused') {
            $this->release(60); // Release back to queue and delay 60 seconds
            return;
        }

        $inserted = 0;

        foreach ($this->chunk as $msg) {
            $msgId = $msg['id'] ?? null;
            if (!$msgId) {
                continue;
            }

            // Extract extended data
            $rawMetadata = [];
            $attachmentsMetadata = [];

            if (isset($msg['replyTo']) && is_array($msg['replyTo'])) {
                $rawMetadata['replyTo'] = $msg['replyTo'];
            }
            if (isset($msg['location']) && is_array($msg['location'])) {
                $rawMetadata['location'] = $msg['location'];
            }
            if (isset($msg['vCards']) && is_array($msg['vCards'])) {
                $rawMetadata['vCards'] = $msg['vCards'];
            }
            if (isset($msg['ack'])) {
                $rawMetadata['ack'] = $msg['ack'];
            }
            if (isset($msg['ackName'])) {
                $rawMetadata['ackName'] = $msg['ackName'];
            }

            if (isset($msg['hasMedia']) && $msg['hasMedia'] && isset($msg['media'])) {
                $attachmentsMetadata[] = $msg['media'];
            }

            $sourceTimestamp = null;
            if (isset($msg['timestamp'])) {
                $sourceTimestamp = Carbon::createFromTimestamp($msg['timestamp']);
            }

            $model = ContactMessage::firstOrCreate(
                ['waha_message_id' => $msgId],
                [
                    'contact_id' => $contact->id,
                    'direction' => ($msg['fromMe'] ?? false) ? 'outbound' : 'inbound',
                    'content' => $msg['body'] ?? '',
                    'body' => $msg['body'] ?? '',
                    'channel' => 'whatsapp',
                    'source' => 'waha_api',
                    'status' => 'delivered',
                    'raw_metadata' => !empty($rawMetadata) ? $rawMetadata : null,
                    'attachments_metadata' => !empty($attachmentsMetadata) ? $attachmentsMetadata : null,
                    'source_timestamp' => $sourceTimestamp,
                    'external_id' => $msgId,
                    'sender_identifier' => $msg['from'] ?? null,
                ]
            );

            if ($model->wasRecentlyCreated) {
                $inserted++;
            }
        }

        Log::info("ProcessWahaMessageChunkJob: Inserted {$inserted} messages for contact {$this->contactId}.");

        if ($process) {
            $process->refresh();
            
            $config = $process->config ?? [];
            $config['nexus_messages_inserted'] = ($config['nexus_messages_inserted'] ?? 0) + $inserted;
            
            $processed = ($process->processed_items ?? 0) + count($this->chunk);
            $total = $process->total_items ?? 1;
            
            $progress = $total > 0 ? round(($processed / $total) * 100) : 0;
            
            $process->update([
                'processed_items' => $processed,
                'progress' => $progress > 100 ? 100 : $progress,
                'config' => $config
            ]);
            
            // Check if fully complete
            if ($processed >= $total) {
                $process->update(['status' => 'completed', 'completed_at' => now(), 'progress' => 100]);
                broadcast(new \App\Events\JobProgressUpdated($process->id, 'sync_messages', 100, $processed, $total, 'completed', 'Message synchronization complete.'));
            } else {
                broadcast(new \App\Events\JobProgressUpdated($process->id, 'sync_messages', $progress, $processed, $total, 'running', "Processing chunk..."));
            }
        }
    }
}
