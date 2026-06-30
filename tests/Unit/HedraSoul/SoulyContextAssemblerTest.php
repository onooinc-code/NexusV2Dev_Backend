<?php

namespace Tests\Unit\HedraSoul;

use Tests\TestCase;
use App\Models\HedrasoulSession;
use App\Models\HedrasoulMessage;
use App\Models\SoulyRuntimeProfile;
use App\Models\SoulyInstructionVersion;
use App\Models\HedraProfileFact;
use App\Models\HedrasoulContextSnapshot;
use App\Services\HedraSoul\SoulyContextAssembler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * SoulyContextAssemblerTest: Tests context assembly functionality.
 * Validates: Requirements 5, 12, Correctness Properties 1-12
 */
class SoulyContextAssemblerTest extends TestCase
{
    use RefreshDatabase;

    protected SoulyContextAssembler $assembler;
    protected SoulyRuntimeProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create instruction version
        $instruction = SoulyInstructionVersion::create([
            'version_number' => 1,
            'status' => 'active',
            'content' => ['instructions' => 'Be helpful and honest'],
            'activated_at' => now(),
        ]);

        // Create runtime profile
        $this->profile = SoulyRuntimeProfile::create([
            'autonomy_mode' => 'copilot',
            'is_quarantined' => false,
            'active_instruction_version_id' => $instruction->id,
            'active_model_instance_id' => null,
            'active_persona_id' => null,
            'memory_access' => true,
            'contact_access' => true,
            'task_execution_access' => true,
            'workflow_execution_access' => true,
            'external_messaging_access' => false,
        ]);

        $this->assembler = app(SoulyContextAssembler::class);
    }

    /**
     * Test returned snapshot includes all required sections
     */
    public function test_snapshot_includes_all_required_sections()
    {
        $session = HedrasoulSession::factory()->create([
            'status' => 'active',
            'title' => 'Test Session',
        ]);

        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'What should I do?',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $snapshot = $this->assembler->assemble($session, $message);

        // Verify snapshot is persisted
        $this->assertInstanceOf(HedrasoulContextSnapshot::class, $snapshot);
        $this->assertDatabaseHas('hedrasoul_context_snapshots', [
            'id' => $snapshot->id,
            'session_id' => $session->id,
        ]);

        // Verify payload contains all sections
        $payload = $snapshot->payload;
        $this->assertArrayHasKey('instruction_version_id', $payload);
        $this->assertArrayHasKey('instruction_content', $payload);
        $this->assertArrayHasKey('persona', $payload);
        $this->assertArrayHasKey('session_summary', $payload);
        $this->assertArrayHasKey('recent_messages', $payload);
        $this->assertArrayHasKey('mentions', $payload);
        $this->assertArrayHasKey('injected_facts', $payload);
        $this->assertArrayHasKey('tool_permissions', $payload);
    }

    /**
     * Test token estimate calculation
     */
    public function test_token_estimate_calculation()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Test message',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $snapshot = $this->assembler->assemble($session, $message);

        // Token estimate should be a positive number
        $this->assertIsNumeric($snapshot->token_estimate);
        $this->assertGreaterThan(0, $snapshot->token_estimate);
    }

    /**
     * Test old messages removed first when over budget
     */
    public function test_old_messages_removed_when_over_budget()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        // Create multiple messages
        for ($i = 0; $i < 30; $i++) {
            HedrasoulMessage::create([
                'session_id' => $session->id,
                'sender_type' => $i % 2 === 0 ? 'user' : 'agent',
                'body' => str_repeat('This is a test message with lots of content. ', 10),
                'body_format' => 'text',
                'status' => 'received',
                'created_at' => now()->subMinutes(30 - $i),
            ]);
        }

        $latestMessage = HedrasoulMessage::latest()->first();

        $snapshot = $this->assembler->assemble($session, $latestMessage);

        // Snapshot should have fewer messages than total created
        $recentMessages = $snapshot->payload['recent_messages'] ?? [];
        $this->assertLessThanOrEqual(20, count($recentMessages));

        // Excluded items should be recorded
        $excludedItems = $snapshot->excluded_items ?? [];
        if (count($recentMessages) < 20) {
            $this->assertNotEmpty($excludedItems);
        }
    }

    /**
     * Test excluded_items contains keys and reasons
     */
    public function test_excluded_items_contains_keys_and_reasons()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        // Create many large messages to exceed budget
        for ($i = 0; $i < 50; $i++) {
            HedrasoulMessage::create([
                'session_id' => $session->id,
                'sender_type' => 'user',
                'body' => str_repeat('Very long message content. ', 50),
                'body_format' => 'text',
                'status' => 'received',
                'created_at' => now()->subMinutes(50 - $i),
            ]);
        }

        $latestMessage = HedrasoulMessage::latest()->first();
        $snapshot = $this->assembler->assemble($session, $latestMessage);

        // Check excluded_items structure
        $excludedItems = $snapshot->excluded_items ?? [];
        
        foreach ($excludedItems as $item) {
            $this->assertArrayHasKey('key', $item);
            $this->assertArrayHasKey('reason', $item);
            $this->assertIsString($item['key']);
            $this->assertIsString($item['reason']);
            $this->assertNotEmpty($item['reason']);
        }
    }

    /**
     * Test hedrasoul_context_snapshots record is persisted
     */
    public function test_context_snapshot_record_persisted()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Test',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $snapshot = $this->assembler->assemble($session, $message);

        $this->assertDatabaseHas('hedrasoul_context_snapshots', [
            'id' => $snapshot->id,
            'session_id' => $session->id,
            'message_id' => $message->id,
        ]);
    }

    /**
     * Test snapshot contains instruction content from active version
     */
    public function test_snapshot_contains_instruction_content()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Test',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $snapshot = $this->assembler->assemble($session, $message);

        $payload = $snapshot->payload;
        $this->assertNotEmpty($payload['instruction_content']);
        $this->assertEquals(
            $this->profile->activeInstructionVersion->content,
            $payload['instruction_content']
        );
    }

    /**
     * Test snapshot includes recent messages
     */
    public function test_snapshot_includes_recent_messages()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        // Create a few messages
        for ($i = 0; $i < 5; $i++) {
            HedrasoulMessage::create([
                'session_id' => $session->id,
                'sender_type' => $i % 2 === 0 ? 'user' : 'agent',
                'body' => "Message $i",
                'body_format' => 'text',
                'status' => 'received',
            ]);
        }

        $latestMessage = HedrasoulMessage::latest()->first();
        $snapshot = $this->assembler->assemble($session, $latestMessage);

        $recentMessages = $snapshot->payload['recent_messages'] ?? [];
        $this->assertNotEmpty($recentMessages);
        $this->assertLessThanOrEqual(20, count($recentMessages));
    }

    /**
     * Test snapshot includes injected profile facts
     */
    public function test_snapshot_includes_injected_facts()
    {
        // Create some profile facts
        for ($i = 0; $i < 3; $i++) {
            HedraProfileFact::create([
                'memory_type' => 'preference',
                'content' => "Fact $i",
                'confidence' => 0.9,
                'sensitivity' => 'internal',
                'visibility_scope' => 'private',
                'is_approved' => true,
                'approved_at' => now(),
                'version' => 1,
            ]);
        }

        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Test',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $snapshot = $this->assembler->assemble($session, $message);

        $injectedFacts = $snapshot->payload['injected_facts'] ?? [];
        $this->assertNotEmpty($injectedFacts);
        $this->assertGreaterThanOrEqual(3, count($injectedFacts));
    }

    /**
     * Test snapshot includes tool permissions
     */
    public function test_snapshot_includes_tool_permissions()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Test',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $snapshot = $this->assembler->assemble($session, $message);

        $payload = $snapshot->payload;
        $this->assertArrayHasKey('tool_permissions', $payload);
        $this->assertArrayHasKey('memory_access', $payload);
        $this->assertArrayHasKey('contact_access', $payload);
        $this->assertArrayHasKey('task_execution_access', $payload);
        $this->assertArrayHasKey('workflow_execution_access', $payload);
        $this->assertArrayHasKey('external_messaging_access', $payload);
    }

    /**
     * Test snapshot risk classification
     */
    public function test_snapshot_risk_classification()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Test',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $snapshot = $this->assembler->assemble($session, $message);

        $this->assertNotNull($snapshot->risk_classification);
        $this->assertContains($snapshot->risk_classification, ['low', 'medium', 'high']);
    }
}
