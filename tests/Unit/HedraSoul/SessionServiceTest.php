<?php

namespace Tests\Unit\HedraSoul;

use Tests\TestCase;
use App\Models\HedrasoulSession;
use App\Models\HedrasoulContextSnapshot;
use App\Services\HedraSoul\HedraSoulSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SessionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_new_session()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        $service = app(HedraSoulSessionService::class);
        $session = $service->startSession(['mode' => 'copilot']);

        $this->assertInstanceOf(HedrasoulSession::class, $session);
        $this->assertEquals('copilot', $session->mode);
        $this->assertDatabaseHas('hedrasoul_sessions', [
            'id'                 => $session->id,
            'last_autonomy_mode' => 'copilot',
        ]);
    }

    public function test_it_retrieves_current_session()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        $session = HedrasoulSession::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Session',
            'status' => 'active',
            'last_autonomy_mode' => 'copilot',
        ]);

        $snapshot = HedrasoulContextSnapshot::create([
            'session_id' => $session->id,
            'payload' => [],
        ]);

        $service = app(HedraSoulSessionService::class);
        $current = $service->getCurrentSession();

        $this->assertEquals($session->id, $current->id);
    }

}
