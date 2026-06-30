<?php

namespace App\Services\PeopleConnect;

use App\Models\PeopleConnect\PeopleConnectMessage;
use App\Models\PeopleConnect\PeopleConnectProcessingLog;
use App\Exceptions\PeopleConnect\DuplicateMessageException;
use Carbon\Carbon;

class PeopleConnectMessageService
{
    /**
     * Inserts a new message after performing deduplication checks.
     *
     * @param array $data
     * @return PeopleConnectMessage
     * @throws DuplicateMessageException
     */
    public function insert(array $data): PeopleConnectMessage
    {
        $conversationId = $data['conversation_id'];
        $wahaMessageId = $data['waha_message_id'] ?? null;
        $hash = $data['provider_payload_hash'] ?? null;

        // Dedup check 1: waha_message_id
        if ($wahaMessageId) {
            $exists = PeopleConnectMessage::where('conversation_id', $conversationId)
                ->where('waha_message_id', $wahaMessageId)
                ->exists();

            if ($exists) {
                $this->logDedup($conversationId, $wahaMessageId, 'waha_message_id');
                throw new DuplicateMessageException("Duplicate message detected by waha_message_id: {$wahaMessageId}");
            }
        }

        // Dedup check 2: provider_payload_hash
        if ($hash) {
            $exists = PeopleConnectMessage::where('conversation_id', $conversationId)
                ->where('provider_payload_hash', $hash)
                ->exists();

            if ($exists) {
                $this->logDedup($conversationId, $wahaMessageId, 'provider_payload_hash');
                throw new DuplicateMessageException("Duplicate message detected by hash: {$hash}");
            }
        }

        $message = PeopleConnectMessage::create([
            'conversation_id' => $conversationId,
            'session_id' => $data['session_id'] ?? null,
            'contact_id' => $data['contact_id'],
            'sender_type' => $data['sender_type'],
            'direction' => $data['direction'],
            'body' => $data['body'],
            'status' => $data['status'] ?? 'delivered',
            'waha_message_id' => $wahaMessageId,
            'provider_payload_hash' => $hash,
            'delivered_at' => $data['delivered_at'] ?? now(),
        ]);

        return $message;
    }

    private function logDedup(int $conversationId, ?string $messageId, string $reason): void
    {
        PeopleConnectProcessingLog::create([
            'conversation_id' => $conversationId,
            'event_type' => 'dedup_skipped',
            'description' => "Message insertion skipped due to deduplication check on {$reason}",
            'payload' => ['waha_message_id' => $messageId]
        ]);
    }
}
