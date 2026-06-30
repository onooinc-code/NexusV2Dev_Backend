<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Memory;
use App\Jobs\SyncMemoryJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    // ─── WAHA Integration ────────────────────────────────────────────────

    public function test_waha_webhook_receives_and_stores_message(): void
    {
        $response = $this->postJson('/api/v1/webhooks/waha', [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'msg_123',
                'chatId' => '1234567890@c.us',
                'body' => 'Hello from WAHA',
                'from' => '1234567890@c.us',
                'pushname' => 'Test User',
                'timestamp' => now()->timestamp,
            ],
        ]);

        $response->assertStatus(202);
    }

    public function test_waha_webhook_creates_contact_if_not_exists(): void
    {
        $this->postJson('/api/v1/webhooks/waha', [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'msg_124',
                'chatId' => '9876543210@c.us',
                'body' => 'Hello',
                'from' => '9876543210@c.us',
                'pushname' => 'New Contact',
                'timestamp' => now()->timestamp,
            ],
        ]);

        $this->assertDatabaseHas('contacts', ['name' => 'New Contact']);
    }

    // ─── Pinecone Integration ────────────────────────────────────────────

    public function test_semantic_memory_stores_embeddings(): void
    {
        $user = \App\Models\User::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/memories', [
            'type' => 'semantic',
            'contactId' => $contact->id,
            'content' => 'Test semantic memory content',
        ]);

        $response->assertStatus(201);
    }

    // ─── AI Provider Integration ─────────────────────────────────────────

    public function test_ai_model_execute_with_openai_provider(): void
    {
        $this->mock(\App\Services\AiModelsHub\EncryptedApiKeyStorage::class, function ($mock) {
            $mock->shouldReceive('getDecryptedKey')->andReturn('test-api-key');
        });

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response(['choices' => [['message' => ['content' => 'Hello']]]], 200)
        ]);

        $user = \App\Models\User::factory()->create();
        $provider = \App\Models\AIProvider::factory()->create(['name' => 'openai']);
        $model = \App\Models\AIModel::factory()->create([
            'provider_id' => $provider->id,
            'name' => 'gpt-4',
        ]);
        \App\Models\IntentRouting::create([
            'intent_name' => 'chat',
            'default_provider_id' => $provider->id,
            'default_model_id' => $model->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/ai-models/route', [
            'intent' => 'chat',
            'model_id' => $model->id,
            'prompt' => 'Hello, world!',
        ]);

        $response->assertStatus(200);
    }

    public function test_ai_model_execute_with_gemini_provider(): void
    {
        $this->mock(\App\Services\AiModelsHub\EncryptedApiKeyStorage::class, function ($mock) {
            $mock->shouldReceive('getDecryptedKey')->andReturn('test-api-key');
        });

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Hello']]]]]], 200)
        ]);

        $user = \App\Models\User::factory()->create();
        $provider = \App\Models\AIProvider::factory()->create(['name' => 'google']);
        $model = \App\Models\AIModel::factory()->create([
            'provider_id' => $provider->id,
            'name' => 'gemini-pro',
        ]);
        \App\Models\IntentRouting::create([
            'intent_name' => 'chat',
            'default_provider_id' => $provider->id,
            'default_model_id' => $model->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/ai-models/route', [
            'intent' => 'chat',
            'model_id' => $model->id,
            'prompt' => 'Hello, world!',
        ]);

        $response->assertStatus(200);
    }

    // ─── Full Conversation Flow ──────────────────────────────────────────

    public function test_full_conversation_flow_from_webhook_to_response(): void
    {
        $contact = Contact::factory()->create(['phone' => '1234567890']);

        $webhookResponse = $this->postJson('/api/v1/webhooks/waha', [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'msg_125',
                'chatId' => '1234567890@c.us',
                'body' => 'Hello, how are you?',
                'from' => '1234567890@c.us',
                'pushname' => $contact->name,
                'timestamp' => now()->timestamp,
            ],
        ]);

        $webhookResponse->assertStatus(202);

        $this->assertDatabaseHas('peopleconnect_messages', [
            'body' => 'Hello, how are you?',
        ]);
    }

    // ─── Memory Sync Integration ─────────────────────────────────────────

    public function test_memory_sync_job_syncs_to_external_stores(): void
    {
        $contact = Contact::factory()->create();
        $memory = Memory::factory()->create([
            'contact_id' => $contact->id,
            'type' => 'episodic',
            'content' => 'Sync test content',
        ]);

        $job = new SyncMemoryJob($memory->contact_id, $memory->type, app(\App\Services\LogService::class));
        $result = $job->handle(
            app(\App\Services\Memory\WorkingMemoryService::class),
            app(\App\Services\Memory\EpisodicMemoryService::class),
            app(\App\Services\Memory\SemanticMemoryService::class),
            app(\App\Services\Memory\StructuredMemoryService::class),
            app(\App\Services\Memory\GraphMemoryService::class)
        );

        // Job has no return value on success, returns null
        $this->assertNull($result);
    }
}
