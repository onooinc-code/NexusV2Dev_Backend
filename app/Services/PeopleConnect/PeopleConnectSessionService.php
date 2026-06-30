<?php

namespace App\Services\PeopleConnect;

use App\Models\PeopleConnect\PeopleConnectConversation;
use App\Models\PeopleConnect\PeopleConnectSession;
use Carbon\Carbon;

class PeopleConnectSessionService
{
    /**
     * Resolves the open session for a conversation or creates a new one.
     * Closes an existing session if it has been inactive for more than 2 hours.
     *
     * @param PeopleConnectConversation $conv
     * @param Carbon $messageTime
     * @return PeopleConnectSession
     */
    public function resolveOrOpen(PeopleConnectConversation $conv, Carbon $messageTime): PeopleConnectSession
    {
        $openSession = $conv->sessions()->where('status', 'open')->first();

        if ($openSession) {
            $lastMessageAt = $conv->last_message_at;
            
            // If more than 2 hours have passed since the last message, close the session
            if ($lastMessageAt && $lastMessageAt->copy()->addHours(2)->lt($messageTime)) {
                $openSession->update([
                    'status' => 'closed',
                    'closed_at' => $messageTime,
                    'closed_reason' => 'inactivity',
                ]);
                $openSession = null;
            }
        }

        if (!$openSession) {
            $openSession = $conv->sessions()->create([
                'contact_id' => $conv->contact_id,
                'status' => 'open',
                'opened_at' => $messageTime,
                'message_count' => 0,
            ]);
        }

        return $openSession;
    }
}
