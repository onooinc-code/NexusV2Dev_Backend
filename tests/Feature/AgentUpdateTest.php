<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_update_merges_settings()
    {
        $user = User::factory()->create();
        $agent = Agent::factory()->create([
            'owner_id' => $user->id,
            'settings' => ['existing_key' => 'existing_value'],
        ]);

        $response = $this->actingAs($user)->putJson("/api/v1/agents/{$agent->id}", [
            'name' => 'Updated Agent Name',
            'temperature' => 0.7,
            'max_tokens' => 1500,
            'guidelines' => 'These are some test guidelines.',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('agents', [
            'id' => $agent->id,
            'name' => 'Updated Agent Name',
        ]);

        $agent->refresh();
        dump('Agent Settings:', $agent->settings);
        $this->assertEquals(0.7, $agent->settings['temperature'] ?? null);
        $this->assertEquals(1500, $agent->settings['max_tokens'] ?? null);
        $this->assertEquals('These are some test guidelines.', $agent->settings['guidelines'] ?? null);
        $this->assertEquals('existing_value', $agent->settings['existing_key'] ?? null);
    }
}
