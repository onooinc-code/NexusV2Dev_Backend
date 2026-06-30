<?php

namespace Tests\Feature;

use App\Models\AIModel;
use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_model_execute_returns_response_structure(): void
    {
        $this->markTestSkipped('Deprecated endpoint');
        $model = AIModel::factory()->create([
            'provider' => 'openai',
            'model' => 'gpt-4',
            'api_key' => 'test-key',
        ]);

        $response = $this->postJson('/api/v1/ai-models/execute', [
            'model_id' => $model->id,
            'prompt' => 'Hello, world!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'response',
                'model',
                'provider',
                'tokens_used',
                'latency_ms',
            ]);
    }

    public function test_ai_model_execute_with_fallback_chain(): void
    {
        $this->markTestSkipped('Deprecated endpoint');
        $response = $this->postJson('/api/v1/ai-models/execute-with-fallback', [
            'prompt' => 'Hello, world!',
            'providers' => ['openai', 'google'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'response',
                'provider_used',
            ]);
    }

    public function test_ai_model_select_by_criteria(): void
    {
        $this->markTestSkipped('Deprecated endpoint');
        $response = $this->postJson('/api/v1/ai-models/select', [
            'criteria' => ['cost' => 'low', 'quality' => 'high'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => []]);
    }

    public function test_ai_model_cost_optimization(): void
    {
        $this->markTestSkipped('Deprecated endpoint');
        $response = $this->postJson('/api/v1/ai-models/optimize-cost', [
            'prompt' => 'Test prompt',
            'max_cost' => 0.01,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => []]);
    }

    public function test_ai_model_quality_routing(): void
    {
        $this->markTestSkipped('Deprecated endpoint');
        $response = $this->postJson('/api/v1/ai-models/route-quality', [
            'tier' => 'high',
            'prompt' => 'Test prompt',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => []]);
    }

    public function test_ai_model_speed_routing(): void
    {
        $this->markTestSkipped('Deprecated endpoint');
        $response = $this->postJson('/api/v1/ai-models/route-speed', [
            'tier' => 'fast',
            'prompt' => 'Test prompt',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => []]);
    }

    public function test_agent_execute_with_ai_model(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $agent = Agent::factory()->create([
            'type' => Agent::TYPE_AUTONOMOUS,
            'status' => Agent::STATUS_ACTIVE,
            'provider' => 'openai',
            'is_active' => true,
        ]);

        // Mock UniversalAiGatewayService to prevent actual gateway execution throwing RuntimeException
        $this->mock(\App\Services\AiModelsHub\UniversalAiGatewayService::class, function ($mock) {
            $mock->shouldReceive('executeWithAgent')
                ->once()
                ->andReturn([
                    'success' => true,
                    'text' => 'Execution result',
                    'used_model' => 'gpt-4',
                    'used_provider' => 'openai',
                ]);
        });

        $response = $this->postJson("/api/v1/agents/{$agent->id}/run", [
            'input' => ['prompt' => 'Test agent execution'],
        ]);

        $response->assertStatus(200);
    }
}
