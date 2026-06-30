<?php

namespace Tests\Feature\HedraSoul;

use Tests\TestCase;
use App\Models\HedrasoulSession;
use App\Models\HedrasoulContextSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HedraSoulApiTest extends TestCase
{
    use RefreshDatabase;



    public function test_it_can_post_a_message()
    {
        $user = User::factory()->create();
        $session = HedrasoulSession::factory()->create([
            'user_id' => $user->id,
            'mode' => 'copilot',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/hedrasoul/sessions/{$session->id}/messages", [
            'body' => 'Feature Test Message',
            'sender_type' => 'user'
        ]);

        $response->assertStatus(202);
        $this->assertDatabaseHas('hedrasoul_messages', [
            'body' => 'Feature Test Message',
            'session_id' => $session->id
        ]);
    }
}
