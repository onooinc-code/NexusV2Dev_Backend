<?php

namespace Tests\Feature\HedraSoul;

use Tests\TestCase;
use App\Models\HedrasoulSession;
use App\Models\HedrasoulMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HedraSoulSessionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test POST /hedrasoul/sessions creates hedrasoul_sessions record and returns 201
     */
    public function test_post_hedrasoul_sessions_creates_session_and_returns_201()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/hedrasoul/sessions', [
                'title' => 'Test Session',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('title', 'Test Session');
        $response->assertJsonPath('status', 'active');

        $this->assertDatabaseHas('hedrasoul_sessions', [
            'title' => 'Test Session',
            'status' => 'active',
        ]);
    }

    /**
     * Test GET /hedrasoul/sessions returns paginated list scoped to authenticated user
     */
    public function test_get_hedrasoul_sessions_returns_paginated_list_for_user()
    {
        // Create sessions
        HedrasoulSession::factory(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/hedrasoul/sessions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_page',
            'last_page',
            'total',
        ]);
    }

    /**
     * Test GET /hedrasoul/sessions/{id} returns session data or 404
     */
    public function test_get_hedrasoul_session_by_id_returns_data_or_404()
    {
        $session = HedrasoulSession::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/hedrasoul/sessions/{$session->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $session->id);
    }

    /**
     * Test GET /hedrasoul/sessions/{id} with invalid id returns 404
     */
    public function test_get_hedrasoul_session_with_invalid_id_returns_404()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/hedrasoul/sessions/99999');

        $response->assertStatus(404);
    }

    /**
     * Test PATCH /hedrasoul/sessions/{id} updates title and returns 200
     */
    public function test_patch_hedrasoul_session_updates_title_and_returns_200()
    {
        $session = HedrasoulSession::factory()->create(['title' => 'Old Title', 'user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/hedrasoul/sessions/{$session->id}", [
                'title' => 'New Title',
                'topic' => 'Discussion',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('title', 'New Title');
        $response->assertJsonPath('topic', 'Discussion');

        $this->assertDatabaseHas('hedrasoul_sessions', [
            'id' => $session->id,
            'title' => 'New Title',
            'topic' => 'Discussion',
        ]);
    }

    /**
     * Test POST /hedrasoul/sessions/{id}/archive sets status='archived' and returns 200
     */
    public function test_post_hedrasoul_session_archive_sets_status_archived_and_returns_200()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active', 'user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/sessions/{$session->id}/archive");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'archived');

        $this->assertDatabaseHas('hedrasoul_sessions', [
            'id' => $session->id,
            'status' => 'archived',
        ]);
    }

    /**
     * Test GET /hedrasoul/sessions returns 401 when called without authentication
     */
    public function test_get_hedrasoul_sessions_returns_401_without_auth()
    {
        $response = $this->getJson('/api/v1/hedrasoul/sessions');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    /**
     * Test POST /hedrasoul/sessions returns 401 when called without authentication
     */
    public function test_post_hedrasoul_sessions_returns_401_without_auth()
    {
        $response = $this->postJson('/api/v1/hedrasoul/sessions', [
            'title' => 'Test Session',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    /**
     * Test GET /hedrasoul/sessions/{id} returns 401 when called without authentication
     */
    public function test_get_hedrasoul_session_by_id_returns_401_without_auth()
    {
        $session = HedrasoulSession::factory()->create();

        $response = $this->getJson("/api/v1/hedrasoul/sessions/{$session->id}");

        $response->assertStatus(401);
    }

    /**
     * Test PATCH /hedrasoul/sessions/{id} returns 401 when called without authentication
     */
    public function test_patch_hedrasoul_session_returns_401_without_auth()
    {
        $session = HedrasoulSession::factory()->create();

        $response = $this->patchJson("/api/v1/hedrasoul/sessions/{$session->id}", [
            'title' => 'New Title',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test POST /hedrasoul/sessions/{id}/archive returns 401 when called without authentication
     */
    public function test_post_hedrasoul_session_archive_returns_401_without_auth()
    {
        $session = HedrasoulSession::factory()->create();

        $response = $this->postJson("/api/v1/hedrasoul/sessions/{$session->id}/archive");

        $response->assertStatus(401);
    }
}
