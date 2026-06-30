<?php

namespace Tests\Feature\PeopleConnect\PropertyBased;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Contact;
use App\Models\PeopleConnect\PeopleConnectConversation;
use App\Models\PeopleConnect\PeopleConnectMessage;
use App\Services\PeopleConnect\PeopleConnectReplyModeService;

class ReplyModeSafetyPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: If reply mode is NOT autopilot, safety check ALWAYS blocks.
     */
    public function test_safety_blocks_when_not_autopilot(): void
    {
        $contact = Contact::factory()->create();
        $conversation = PeopleConnectConversation::create([
            'contact_id' => $contact->id,
            'channel' => 'whatsapp',
            'provider' => 'waha',
            'provider_conversation_id' => '123@c.us',
            'reply_mode_effective' => 'manual',
        ]);
        
        $message = PeopleConnectMessage::create([
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'provider_payload_hash' => 'hash123',
            'sender_type' => 'contact',
            'direction' => 'inbound',
            'body' => 'trigger'
        ]);

        $service = app(PeopleConnectReplyModeService::class);

        $modes = ['manual', 'copilot', 'ai_only'];

        foreach ($modes as $mode) {
            $conversation->update(['reply_mode_effective' => $mode]);
            
            $result = $service->checkAutopilotSafety($contact->id, $message);
            $this->assertTrue($result['blocked'], "Failed blocking on mode: {$mode}");
        }
    }

    /**
     * Property: Rate limiting blocks autopilot if > 5 messages sent in last 5 minutes.
     */
    public function test_safety_blocks_on_rate_limit(): void
    {
        $contact = Contact::factory()->create();
        $conversation = PeopleConnectConversation::create([
            'contact_id' => $contact->id,
            'channel' => 'whatsapp',
            'provider' => 'waha',
            'provider_conversation_id' => '123@c.us',
            'reply_mode_effective' => 'autopilot',
        ]);
        
        $message = PeopleConnectMessage::create([
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'provider_payload_hash' => 'hash123',
            'sender_type' => 'contact',
            'direction' => 'inbound',
            'body' => 'trigger'
        ]);

        // Insert 5 recent agent messages
        for ($i = 0; $i < 5; $i++) {
            PeopleConnectMessage::create([
                'conversation_id' => $conversation->id,
                'contact_id' => $contact->id,
                'provider_payload_hash' => 'agenthash' . $i,
                'sender_type' => 'agent',
                'direction' => 'outbound',
                'body' => 'agent reply',
                'created_at' => now()->subMinutes(1)
            ]);
        }

        $service = app(PeopleConnectReplyModeService::class);
        $result = $service->checkAutopilotSafety($contact->id, $message);
        
        $this->assertTrue($result['blocked']);
        $this->assertEquals('Rate limit exceeded', $result['reason']);
    }
}
