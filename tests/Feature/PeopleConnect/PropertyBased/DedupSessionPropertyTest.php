<?php

namespace Tests\Feature\PeopleConnect\PropertyBased;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Contact;
use App\Services\PeopleConnect\WahaWebhookIngestionService;

class DedupSessionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: Processing the exact same payload N times results in exactly 1 message and 1 session.
     */
    public function test_idempotency_property_for_deduplication(): void
    {
        $contact = Contact::factory()->create([
            'whatsapp_number' => '1234567890'
        ]);

        $payload = [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'msg_prop_' . uniqid(),
                'timestamp' => time(),
                'chatId' => '1234567890@c.us',
                'from' => '1234567890@c.us',
                'body' => 'Property test message',
                'type' => 'chat'
            ]
        ];

        $service = app(WahaWebhookIngestionService::class);

        // Fuzz loop: Simulate 10 duplicate concurrent-like arrivals
        for ($i = 0; $i < 10; $i++) {
            $service->ingest($payload);
        }

        // Assert only 1 conversation was created
        $this->assertDatabaseCount('peopleconnect_conversations', 1);

        // Assert only 1 session was created
        $this->assertDatabaseCount('peopleconnect_sessions', 1);

        // Assert exactly 1 message was created
        $this->assertDatabaseCount('peopleconnect_messages', 1);
    }
}
