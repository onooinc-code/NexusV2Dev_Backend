<?php

namespace Tests\Feature\PeopleConnect;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Contact;
use App\Models\PeopleConnect\PeopleConnectConversation;
use App\Models\PeopleConnect\PeopleConnectSession;
use App\Models\PeopleConnect\PeopleConnectMessage;
use App\Models\User;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create an admin user for Sanctum auth
        $this->user = User::factory()->create();
    }

    public function test_stats_endpoint_returns_correct_data()
    {
        $contact = Contact::create(['name' => 'Test Contact', 'whatsapp_number' => '12345']);
        $conversation = PeopleConnectConversation::create([
            'contact_id' => $contact->id,
            'channel' => 'whatsapp',
            'provider' => 'waha',
            'provider_conversation_id' => '12345@c.us',
            'unread_count' => 1
        ]);
        PeopleConnectSession::create([
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'status' => 'open'
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/people-connect/stats');

        $response->assertStatus(200)
                 ->assertJson([
                     'total_contacts' => 1,
                     'active_sessions' => 1,
                     'unread_conversations' => 1,
                     'status' => 'healthy'
                 ]);
    }

    public function test_search_endpoint_finds_contact()
    {
        $contact = Contact::create(['name' => 'Jane Doe', 'phone' => '987654321', 'whatsapp_number' => '987654321']);
        PeopleConnectConversation::create([
            'contact_id' => $contact->id,
            'channel' => 'whatsapp',
            'provider' => 'waha',
            'provider_conversation_id' => '987654321@c.us',
            'unread_count' => 0
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/people-connect/search?q=Jane');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        $this->assertEquals('Jane Doe', $response->json()[0]['name']);
    }

    public function test_livemsgs_endpoint_returns_messages()
    {
        $contact = Contact::create(['name' => 'Alice', 'whatsapp_number' => '111222']);
        $conversation = PeopleConnectConversation::create([
            'contact_id' => $contact->id,
            'channel' => 'whatsapp',
            'provider' => 'waha',
            'provider_conversation_id' => '111222@c.us',
        ]);
        PeopleConnectMessage::create([
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'sender_type' => 'contact',
            'direction' => 'inbound',
            'body' => 'Hello World',
            'status' => 'delivered'
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/people-connect/livemsgs');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
        $this->assertEquals('Hello World', $response->json('data')[0]['body']);
    }
}
