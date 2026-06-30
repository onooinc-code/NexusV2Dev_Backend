<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_messages_payload_contains_channel_thread_and_metadata(): void
    {
        $this->markTestSkipped('Legacy conversation hub endpoints deprecated.');
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $conversation = \App\Models\Conversation::factory()->create();

        $response = $this->getJson("/api/v1/conversations/{$conversation->id}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'conversation_id',
                    'messages' => [
                        ['id', 'conversation_id', 'sender', 'channel', 'thread_id', 'content', 'metadata', 'created_at'],
                    ],
                ],
            ]);

        $this->assertEquals($conversation->id, $response->json('data.conversation_id'));
    }

    public function test_send_message_accepts_channel_thread_and_metadata(): void
    {
        $this->markTestSkipped('Legacy conversation hub endpoints deprecated.');
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $conversation = \App\Models\Conversation::factory()->create();

        $payload = [
            'sender' => 'agent',
            'content' => 'Hello from the hub',
            'channel' => 'email',
            'thread_id' => 'thread-789',
            'metadata' => ['urgent' => true],
        ];

        $response = $this->postJson("/api/v1/conversations/{$conversation->id}/send-message", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.conversation_id', $conversation->id)
            ->assertJsonPath('data.sender', 'agent')
            ->assertJsonPath('data.channel', 'email')
            ->assertJsonPath('data.thread_id', 'thread-789')
            ->assertJsonPath('data.metadata.urgent', true);
    }
}
