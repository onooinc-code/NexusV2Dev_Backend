<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AIProvider;
use App\Models\AIModel;
use App\Models\AIApiKey;
use App\Models\IntentRouting;
use App\Models\User;

class AiRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_handles_an_ai_request_via_intent_routing()
    {
        // Create provider
        $provider = AIProvider::factory()->create([
            'name' => 'Test Provider',
            'base_url' => 'https://api.test.com',
            'generate_endpoint' => '/generate',
            'auth_header_format' => 'Bearer {key}',
            'payload_format' => 'openai',
            'is_active' => true,
        ]);

        // Create API key
        $apiKey = AIApiKey::factory()->create([
            'provider_id' => $provider->id,
            'key_hash' => encrypt('test-key'),
            'is_active' => true,
        ]);

        // Create model
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
            'name' => 'test-model',
            'context_window' => 4096,
            'input_cost_per_m' => 0.001,
            'output_cost_per_m' => 0.002,
        ]);

        // Create intent routing
        $intent = IntentRouting::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'intent_name' => 'test-intent',
            'default_provider_id' => $provider->id,
            'default_model_id' => $model->id,
        ]);

        // Mock the HTTP response for AI generation
        \Illuminate\Support\Facades\Http::fake([
            // Mock the generation endpoint
            '*' => \Illuminate\Support\Facades\Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'This is a test response',
                        ]
                    ]
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 5,
                    'total_tokens' => 15,
                ]
            ], 200, ['Content-Type' => 'application/json'])
        ]);

        $response = $this->postJson('/api/v1/ai/request', [
            'intent' => 'test-intent',
            'prompt' => 'Hello, world!',
            'options' => [
                'temperature' => 0.7,
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'provider',
            'model',
            'content',
            'usage',
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('This is a test response', $response->json('content'));
        $this->assertEquals('test-model', $response->json('model'));
    }

    /** @test */
    public function it_returns_error_for_unknown_intent()
    {
        $response = $this->postJson('/api/v1/ai/request', [
            'intent' => 'unknown-intent',
            'prompt' => 'Hello, world!',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Intent not found', $response->json('error'));
    }

    /** @test */
    public function it_uses_fallback_when_primary_provider_fails()
    {
        // Create primary provider (will fail)
        $primaryProvider = AIProvider::factory()->create([
            'name' => 'Primary Provider',
            'base_url' => 'https://api.primary.com',
            'generate_endpoint' => '/generate',
            'auth_header_format' => 'Bearer {key}',
            'payload_format' => 'openai',
            'is_active' => true,
        ]);

        // Create fallback provider (will succeed)
        $fallbackProvider = AIProvider::factory()->create([
            'name' => 'Fallback Provider',
            'base_url' => 'https://api.fallback.com',
            'generate_endpoint' => '/generate',
            'auth_header_format' => 'Bearer {key}',
            'payload_format' => 'openai',
            'is_active' => true,
        ]);

        // Create API keys
        AIApiKey::factory()->create([
            'provider_id' => $primaryProvider->id,
            'key_hash' => encrypt('primary-key'),
            'is_active' => true,
        ]);

        AIApiKey::factory()->create([
            'provider_id' => $fallbackProvider->id,
            'key_hash' => encrypt('fallback-key'),
            'is_active' => true,
        ]);

        // Create model
        $model = AIModel::factory()->create([
            'provider_id' => $fallbackProvider->id,
            'name' => 'fallback-model',
            'context_window' => 4096,
            'input_cost_per_m' => 0.001,
            'output_cost_per_m' => 0.002,
        ]);

        // Create intent routing with fallback
        $intent = IntentRouting::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'intent_name' => 'test-intent',
            'default_provider_id' => $primaryProvider->id,
            'fallback_provider_id' => $fallbackProvider->id,
            'default_model_id' => $model->id,
            'fallback_model_id' => $model->id,
        ]);

        // Mock HTTP responses - primary fails, fallback succeeds
        \Illuminate\Support\Facades\Http::fake([
            // Primary provider fails
            'https://api.primary.com*' => \Illuminate\Support\Facades\Http::response('Internal Server Error', 500),
            // Fallback provider succeeds
            'https://api.fallback.com*' => \Illuminate\Support\Facades\Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Fallback response',
                        ]
                    ]
                ],
                'usage' => [
                    'prompt_tokens' => 8,
                    'completion_tokens' => 4,
                    'total_tokens' => 12,
                ]
            ], 200, ['Content-Type' => 'application/json'])
        ]);

        $response = $this->postJson('/api/v1/ai/request', [
            'intent' => 'test-intent',
            'prompt' => 'Hello, world!',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'provider' => 'Fallback Provider',
            'model' => 'fallback-model',
            'content' => 'Fallback response',
        ]);
    }
}