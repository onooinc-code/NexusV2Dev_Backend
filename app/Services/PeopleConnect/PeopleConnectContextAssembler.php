<?php

namespace App\Services\PeopleConnect;

use App\Models\PeopleConnect\PeopleConnectConversation;
use App\Models\PeopleConnect\PeopleConnectContextSnapshot;
use App\Models\PeopleConnect\PeopleConnectMessage;
use Carbon\Carbon;

class PeopleConnectContextAssembler
{
    protected int $tokenBudget;

    public function __construct(int $tokenBudget = 8000)
    {
        $this->tokenBudget = $tokenBudget;
    }

    /**
     * Assemble and freeze a context snapshot for the given conversation.
     */
    public function assemble(PeopleConnectConversation $conv): PeopleConnectContextSnapshot
    {
        $contact = $conv->contact;

        $contactProfile = [
            'id' => $contact->id,
            'name' => $contact->name,
            'phone' => $contact->phone,
            'whatsapp_number' => $contact->whatsapp_number,
            'type' => $contact->type,
        ];

        // Get recent messages (newest first), truncating if over token budget
        $messages = $conv->messages()
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get()
            ->reverse()
            ->values();

        $includedMessages = [];
        $excludedMessages = [];
        $tokenEstimate = 0;

        foreach ($messages as $msg) {
            $tokens = (int) ceil(mb_strlen($msg->body ?? '') / 4);
            if ($tokenEstimate + $tokens <= $this->tokenBudget) {
                $includedMessages[] = [
                    'id' => $msg->id,
                    'direction' => $msg->direction,
                    'sender_type' => $msg->sender_type,
                    'body' => $msg->body,
                    'delivered_at' => $msg->delivered_at?->toIso8601String(),
                ];
                $tokenEstimate += $tokens;
            } else {
                $excludedMessages[] = ['id' => $msg->id, 'reason' => 'token_budget_exceeded'];
            }
        }

        // Gather topics
        $topics = $conv->topics()->select('topic', 'mention_count')->get()->toArray();

        // Latest session info
        $latestSession = $conv->sessions()->orderBy('created_at', 'desc')->first();

        $payload = [
            'contact_profile' => $contactProfile,
            'messages' => $includedMessages,
            'topics' => $topics,
            'latest_session_id' => $latestSession?->id,
            'excluded_items' => $excludedMessages,
            'token_estimate' => $tokenEstimate,
        ];

        return PeopleConnectContextSnapshot::create([
            'conversation_id' => $conv->id,
            'contact_id' => $contact->id,
            'token_estimate' => $tokenEstimate,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
