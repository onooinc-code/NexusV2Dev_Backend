<?php

namespace Tests\Feature\HedraSoul;

use Tests\TestCase;
use App\Models\HedrasoulSession;
use App\Models\HedrasoulMessage;
use App\Models\HedrasoulContextSnapshot;
use App\Models\SoulyActionTrace;
use App\Models\User;
use App\Jobs\HedraSoul\ProcessHedraSoulMessageJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class HedraSoulMessageApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private HedrasoulSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->session = HedrasoulSession::factory()->create(['user_id' => $this->user->id]);
    }

    /**
     * Test POST /hedrasoul/sessions/{id}/messages saves record and returns 202
     */
    public function test_post_message_saves_record_and_returns_202()
    {
        Queue::fake();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/sessions/{$this->session->id}/messages", [
                'body' => 'Test message for Souly',
                'body_format' => 'markdown',
            ]);

        $response->assertStatus(202);
        $response->assertJsonPath('status', 'processing');

        $this->assertDatabaseHas('hedrasoul_messages', [
            'session_id' => $this->session->id,
            'body' => 'Test message for Souly',
            'sender_type' => 'user',
        ]);
    }

    /**
     * Test ProcessHedraSoulMessageJob is dispatched
     */
    public function test_process_hedrasoul_message_job_is_dispatched()
    {
        Queue::fake();

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/sessions/{$this->session->id}/messages", [
                'body' => 'Test message',
            ]);

        Queue::assertPushed(ProcessHedraSoulMessageJob::class);
    }

    /**
     * Test GET /hedrasoul/sessions/{id}/messages returns paginated messages
     */
    public function test_get_session_messages_returns_paginated_messages()
    {
        HedrasoulMessage::factory(5)->create([
            'session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/hedrasoul/sessions/{$this->session->id}/messages");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'current_page',
            'total',
        ]);
        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    /**
     * Test GET /hedrasoul/messages/{id}/trace returns associated SoulyActionTrace or 404
     */
    public function test_get_message_trace_returns_trace_or_404()
    {
        $trace = SoulyActionTrace::factory()->create([
            'trace_id' => 'test-trace-123',
        ]);

        $message = HedrasoulMessage::factory()->create([
            'trace_id' => 'test-trace-123',
            'session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/hedrasoul/messages/{$message->id}/trace");

        $response->assertStatus(200);
        $response->assertJsonPath('trace_id', 'test-trace-123');
    }

    /**
     * Test GET /hedrasoul/messages/{id}/trace returns 404 when trace not found
     */
    public function test_get_message_trace_returns_404_when_trace_not_found()
    {
        $message = HedrasoulMessage::factory()->create([
            'trace_id' => null,
            'session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/hedrasoul/messages/{$message->id}/trace");

        $response->assertStatus(404);
    }

    /**
     * Test GET /hedrasoul/messages/{id}/context returns associated HedrasoulContextSnapshot or 404
     */
    public function test_get_message_context_returns_context_snapshot_or_404()
    {
        $context = HedrasoulContextSnapshot::factory()->create([
            'session_id' => $this->session->id,
        ]);

        $message = HedrasoulMessage::factory()->create([
            'context_snapshot_id' => $context->id,
            'session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/hedrasoul/messages/{$message->id}/context");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $context->id);
    }

    /**
     * Test GET /hedrasoul/messages/{id}/context returns 404 when context not found
     */
    public function test_get_message_context_returns_404_when_context_not_found()
    {
        $message = HedrasoulMessage::factory()->create([
            'context_snapshot_id' => null,
            'session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/hedrasoul/messages/{$message->id}/context");

        $response->assertStatus(404);
    }

    /**
     * Test POST /hedrasoul/sessions/{id}/messages returns 401 without authentication
     */
    public function test_post_message_returns_401_without_auth()
    {
        $response = $this->postJson("/api/v1/hedrasoul/sessions/{$this->session->id}/messages", [
            'body' => 'Test message',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test GET /hedrasoul/sessions/{id}/messages returns 401 without authentication
     */
    public function test_get_session_messages_returns_401_without_auth()
    {
        $response = $this->getJson("/api/v1/hedrasoul/sessions/{$this->session->id}/messages");

        $response->assertStatus(401);
    }

    /**
     * Test GET /hedrasoul/messages/{id}/trace returns 401 without authentication
     */
    public function test_get_message_trace_returns_401_without_auth()
    {
        $message = HedrasoulMessage::factory()->create();

        $response = $this->getJson("/api/v1/hedrasoul/messages/{$message->id}/trace");

        $response->assertStatus(401);
    }

    /**
     * Test GET /hedrasoul/messages/{id}/context returns 401 without authentication
     */
    public function test_get_message_context_returns_401_without_auth()
    {
        $message = HedrasoulMessage::factory()->create();

        $response = $this->getJson("/api/v1/hedrasoul/messages/{$message->id}/context");

        $response->assertStatus(401);
    }
}
