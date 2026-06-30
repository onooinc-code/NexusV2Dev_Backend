<?php

namespace Tests\Feature;

use App\Events\AgentExecuted;
use App\Events\ContactCreated;
use App\Events\MemoryIndexed;
use App\Events\MessageReceived;
use App\Events\MessageSent;
use App\Events\WorkflowCompleted;
use App\Events\WorkflowStarted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_created_event_is_dispatched(): void
    {
        Event::fake([ContactCreated::class]);
        $this->actingAs(\App\Models\User::factory()->create(), 'sanctum');
        $response = $this->postJson('/api/v1/contacts', [
            'name' => 'Test Contact',
            'email' => 'test@example.com',
            'type' => 'contact',
        ]);
        $response->assertStatus(201);
        Event::assertDispatched(ContactCreated::class);
    }

    public function test_message_received_event_is_dispatched(): void
    {
        Event::fake([\App\Events\PeopleConnect\MessageReceived::class]);
        $response = $this->postJson('/api/v1/webhooks/waha', [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'msg_123',
                'chatId' => '1234567890@c.us',
                'content' => 'Hello',
                'from' => 'Test User',
                'body' => 'Hello',
            ],
        ]);
        $response->assertStatus(202);
        Event::assertDispatched(\App\Events\PeopleConnect\MessageReceived::class);
    }

    public function test_workflow_started_event_is_dispatched(): void
    {
        Event::fake([WorkflowStarted::class]);
        $workflow = \App\Models\Workflow::factory()->create([
            'status' => \App\Models\Workflow::STATUS_DRAFT,
            'is_active' => true,
            'steps' => [['name' => 'Step 1', 'action' => 'log', 'message' => 'Test']],
        ]);
        $this->actingAs(\App\Models\User::factory()->create(), 'sanctum');
        $response = $this->postJson("/api/v1/workflows/{$workflow->id}/execute");
        $response->assertStatus(202);
        Event::assertDispatched(WorkflowStarted::class);
    }

    public function test_workflow_completed_event_is_dispatched(): void
    {
        Event::fake([WorkflowCompleted::class]);
        $workflow = \App\Models\Workflow::factory()->create([
            'status' => \App\Models\Workflow::STATUS_DRAFT,
            'is_active' => true,
            'steps' => [['name' => 'Step 1', 'action' => 'log', 'message' => 'Test']],
        ]);
        $this->actingAs(\App\Models\User::factory()->create(), 'sanctum');
        $response = $this->postJson("/api/v1/workflows/{$workflow->id}/execute");
        $response->assertStatus(202);
        Event::assertDispatched(WorkflowCompleted::class);
    }

    public function test_agent_executed_event_is_dispatched(): void
    {
        Event::fake([\App\Events\AgentCompleted::class]);
        $agent = \App\Models\Agent::factory()->create([
            'status' => \App\Models\Agent::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        
        $this->mock(\App\Services\AiModelsHub\UniversalAiGatewayService::class, function ($mock) {
            $mock->shouldReceive('executeWithAgent')->andReturn(['success' => true, 'output' => 'test']);
        });

        $this->actingAs(\App\Models\User::factory()->create(), 'sanctum');
        $response = $this->postJson("/api/v1/agents/{$agent->id}/run", ['input' => ['message' => 'test']]);
        if (!$response->isSuccessful()) {
            dd($response->json());
        }
        $response->assertSuccessful();
        Event::assertDispatched(\App\Events\AgentCompleted::class);
    }
}
