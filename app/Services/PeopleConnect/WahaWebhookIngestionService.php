<?php

namespace App\Services\PeopleConnect;

use App\Jobs\ProcessWahaWebhookJob;
use App\Models\PeopleConnect\PeopleConnectRawProviderEvent;
use Illuminate\Support\Facades\Log;

class WahaWebhookIngestionService
{
    /**
     * Ingests a WAHA webhook payload.
     *
     * @param array $payload
     * @return void
     */
    public function ingest(array $payload): void
    {
        $session = $payload['session'] ?? null;
        $messageId = $payload['payload']['id'] ?? null;

        if (!$session || !$messageId) {
            Log::warning('WAHA Webhook Ingestion: Missing session or payload id', ['payload' => $payload]);
            return;
        }

        // Deduplication check at raw event level
        $existing = PeopleConnectRawProviderEvent::where('session_name', $session)
            ->where('payload->payload->id', $messageId)
            ->exists();

        if ($existing) {
            Log::info('WAHA Webhook Ingestion: Duplicate payload detected, skipping.', ['session' => $session, 'message_id' => $messageId]);
            return;
        }

        // Store raw provider event
        $rawEvent = PeopleConnectRawProviderEvent::create([
            'event_type' => $payload['event'] ?? 'unknown',
            'payload' => $payload,
            'session_name' => $session,
            'received_at' => now(),
            'processing_status' => 'pending',
        ]);

        ProcessWahaWebhookJob::dispatch($payload, $rawEvent->id);
    }
}
