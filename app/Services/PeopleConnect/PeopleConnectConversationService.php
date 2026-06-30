<?php

namespace App\Services\PeopleConnect;

use App\Models\PeopleConnect\PeopleConnectConversation;

class PeopleConnectConversationService
{
    /**
     * Resolves an existing conversation or creates a new one.
     *
     * @param int $contactId
     * @param string $channel e.g., 'whatsapp'
     * @param string $chatId WAHA chatId
     * @return PeopleConnectConversation
     */
    public function resolveOrCreate(int $contactId, string $channel, string $chatId): PeopleConnectConversation
    {
        $provider = 'waha'; // Assuming waha is the only provider for now

        $conversation = PeopleConnectConversation::firstOrCreate(
            [
                'contact_id' => $contactId,
                'channel' => $channel,
                'provider' => $provider,
            ],
            [
                'provider_conversation_id' => $chatId,
                'status' => 'active',
                'unread_count' => 0,
                'reply_mode_effective' => 'manual',
            ]
        );

        return $conversation;
    }
}
