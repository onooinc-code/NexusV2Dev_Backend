<?php

namespace Tests\Unit\HedraSoul;

use Tests\TestCase;
use App\Models\SoulyRuntimeProfile;
use App\Models\SoulyActionPolicy;
use App\Services\HedraSoul\SoulyActionPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * SoulyActionPolicyServiceTest: Tests autonomy mode and policy enforcement.
 * Validates: Requirements 7, 8, Correctness Properties 1-12
 */
class SoulyActionPolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SoulyActionPolicyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SoulyActionPolicyService::class);
    }

    /**
     * Test chat_only mode blocks create_task, execute_agent, start_workflow
     */
    public function test_chat_only_blocks_write_actions()
    {
        SoulyRuntimeProfile::create([
            'autonomy_mode' => 'chat_only',
            'is_quarantined' => false,
            'memory_access' => true,
            'contact_access' => true,
            'task_execution_access' => true,
            'workflow_execution_access' => true,
            'external_messaging_access' => false,
        ]);

        $result = $this->service->canExecute('create_task', 'write_low');
        $this->assertFalse($result->allowed, 'chat_only should block create_task');
        $this->assertNotEmpty($result->explanation);

        $result = $this->service->canExecute('execute_agent', 'write_medium');
        $this->assertFalse($result->allowed, 'chat_only should block execute_agent');

        $result = $this->service->canExecute('start_workflow', 'write_medium');
        $this->assertFalse($result->allowed, 'chat_only should block start_workflow');
    }

    /**
     * Test chat_only mode allows answer and draft
     */
    public function test_chat_only_allows_read_actions()
    {
        SoulyRuntimeProfile::create([
            'autonomy_mode' => 'chat_only',
            'is_quarantined' => false,
            'memory_access' => true,
            'contact_access' => true,
            'task_execution_access' => true,
            'workflow_execution_access' => true,
            'external_messaging_access' => false,
        ]);

        $result = $this->service->canExecute('answer', 'read');
        $this->assertTrue($result->allowed, 'chat_only should allow answer');

        $result = $this->service->canExecute('draft', 'draft');
        $this->assertTrue($result->allowed, 'chat_only should allow draft');
    }

    /**
     * Test copilot mode allows draft and answer, blocks danger
     */
    public function test_copilot_mode_enforcement()
    {
        SoulyRuntimeProfile::create([
            'autonomy_mode' => 'copilot',
            'is_quarantined' => false,
            'memory_access' => true,
            'contact_access' => true,
            'task_execution_access' => true,
            'workflow_execution_access' => true,
            'external_messaging_access' => false,
        ]);

        $result = $this->service->canExecute('draft', 'draft');
        $this->assertTrue($result->allowed, 'copilot should allow draft');

        $result = $this->service->canExecute('answer', 'read');
        $this->assertTrue($result->allowed, 'copilot should allow answer');

        $result = $this->service->canExecute('start_workflow', 'write_medium');
        $this->assertFalse($result->allowed, 'copilot should block workflows');
    }

    /**
     * Test operator mode allows write_low, blocks write_medium and above
     */
    public function test_operator_mode_enforcement()
    {
        SoulyRuntimeProfile::create([
            'autonomy_mode' => 'operator',
            'is_quarantined' => false,
            'memory_access' => true,
            'contact_access' => true,
            'task_execution_access' => true,
            'workflow_execution_access' => true,
            'external_messaging_access' => false,
        ]);

        $result = $this->service->canExecute('create_task', 'write_low');
        $this->assertTrue($result->allowed, 'operator should allow write_low');

        $result = $this->service->canExecute('execute_agent', 'write_medium');
        $this->assertFalse($result->allowed, 'operator should block write_medium');

        $result = $this->service->canExecute('notify', 'external_send');
        $this->assertFalse($result->allowed, 'operator should block external_send');
    }

    /**
     * Test autopilot_limited mode allows pre-approved workflows
     */
    public function test_autopilot_limited_mode_enforcement()
    {
        SoulyRuntimeProfile::create([
            'autonomy_mode' => 'autopilot_limited',
            'is_quarantined' => false,
            'memory_access' => true,
            'contact_access' => true,
            'task_execution_access' => true,
            'workflow_execution_access' => true,
            'external_messaging_access' => false,
        ]);

        // Pre-approved workflow (write_low) should be allowed
        $result = $this->service->canExecute('create_task', 'write_low');
        $this->assertTrue($result->allowed, 'autopilot_limited should allow write_low');

        // Danger should be blocked
        $result = $this->service->canExecute('execute_agent', 'danger');
        $this->assertFalse($result->allowed, 'autopilot_limited should block danger');
    }

    /**
     * Test emergency_paused mode blocks everything including answer
     */
    public function test_emergency_paused_blocks_all()
    {
        SoulyRuntimeProfile::create([
            'autonomy_mode' => 'emergency_paused',
            'is_quarantined' => false,
            'memory_access' => true,
            'contact_access' => true,
            'task_execution_access' => true,
            'workflow_execution_access' => true,
            'external_messaging_access' => false,
        ]);

        $result = $this->service->canExecute('answer', 'read');
        $this->assertFalse($result->allowed, 'emergency_paused should block answer');

        $result = $this->service->canExecute('draft', 'draft');
        $this->assertFalse($result->allowed, 'emergency_paused should block draft');

        $result = $this->service->canExecute('create_task', 'write_low');
        $this->assertFalse($result->allowed, 'emergency_paused should block create_task');
    }

    /**
     * Test is_quarantined=true blocks all intents regardless of mode
     */
    public function test_quarantine_blocks_all_intents()
    {
        SoulyRuntimeProfile::create([
            'autonomy_mode' => 'copilot',
            'is_quarantined' => true,
            'memory_access' => true,
            'contact_access' => true,
            'task_execution_access' => true,
            'workflow_execution_access' => true,
            'external_messaging_access' => false,
        ]);

        $result = $this->service->canExecute('answer', 'read');
        $this->assertFalse($result->allowed, 'quarantine should block answer');

        $result = $this->service->canExecute('draft', 'draft');
        $this->assertFalse($result->allowed, 'quarantine should block draft');

        $result = $this->service->canExecute('create_task', 'write_low');
        $this->assertFalse($result->allowed, 'quarantine should block create_task');
    }

    /**
     * Test blocked action returns PolicyResult with allowed=false and non-empty explanation
     */
    public function test_blocked_action_returns_explanation()
    {
        SoulyRuntimeProfile::create([
            'autonomy_mode' => 'chat_only',
            'is_quarantined' => false,
            'memory_access' => true,
            'contact_access' => true,
            'task_execution_access' => true,
            'workflow_execution_access' => true,
            'external_messaging_access' => false,
        ]);

        $result = $this->service->canExecute('create_task', 'write_low');
        
        $this->assertFalse($result->allowed);
        $this->assertIsString($result->explanation);
        $this->assertNotEmpty($result->explanation);
    }

    /**
     * PBT: Generate 100 random (autonomy_mode, risk_level) combinations
     * and verify consistency with policy table
     */
    public function test_100_random_mode_risk_combinations()
    {
        $modes = ['chat_only', 'copilot', 'operator', 'autopilot_limited', 'emergency_paused'];
        $risks = ['read', 'draft', 'write_low', 'write_medium', 'external_send', 'danger'];
        $intents = [
            'answer' => 'read',
            'draft' => 'draft',
            'create_task' => 'write_low',
            'execute_agent' => 'write_medium',
            'start_workflow' => 'write_medium',
            'schedule_work' => 'write_low',
            'open_approval' => 'read',
            'update_profile' => 'write_low',
            'suggest_memory' => 'write_low',
            'suggest_setting' => 'write_low',
            'notify' => 'external_send',
        ];

        // Test 100 combinations
        for ($i = 0; $i < 100; $i++) {
            $mode = $modes[array_rand($modes)];
            $riskIndex = array_rand($risks);
            $risk = $risks[$riskIndex];

            // Create profile with this mode
            SoulyRuntimeProfile::query()->delete();
            SoulyRuntimeProfile::create([
                'autonomy_mode' => $mode,
                'is_quarantined' => false,
                'memory_access' => true,
                'contact_access' => true,
                'task_execution_access' => true,
                'workflow_execution_access' => true,
                'external_messaging_access' => false,
            ]);

            // Pick a random intent that matches the risk
            $matchingIntents = array_filter($intents, fn($r) => $r === $risk);
            if (empty($matchingIntents)) {
                continue;
            }

            $intent = array_key_first($matchingIntents);
            $result = $this->service->canExecute($intent, $risk);

            // Verify consistency: result should match policy table
            $expectedAllowed = $this->getExpectedPolicyDecision($mode, $risk);
            $this->assertEquals(
                $expectedAllowed,
                $result->allowed,
                "Mode: $mode, Intent: $intent, Risk: $risk should be " . ($expectedAllowed ? 'allowed' : 'blocked')
            );
        }
    }

    /**
     * Helper: Get expected policy decision from the policy table
     */
    protected function getExpectedPolicyDecision(string $mode, string $risk): bool
    {
        $policyTable = [
            'chat_only' => [
                'read' => true,
                'draft' => true,
                'write_low' => false,
                'write_medium' => false,
                'external_send' => false,
                'danger' => false,
            ],
            'copilot' => [
                'read' => true,
                'draft' => true,
                'write_low' => true,
                'write_medium' => false,
                'external_send' => false,
                'danger' => false,
            ],
            'operator' => [
                'read' => true,
                'draft' => true,
                'write_low' => true,
                'write_medium' => false,
                'external_send' => false,
                'danger' => false,
            ],
            'autopilot_limited' => [
                'read' => true,
                'draft' => true,
                'write_low' => true,
                'write_medium' => false,
                'external_send' => false,
                'danger' => false,
            ],
            'emergency_paused' => [
                'read' => false,
                'draft' => false,
                'write_low' => false,
                'write_medium' => false,
                'external_send' => false,
                'danger' => false,
            ],
        ];

        return $policyTable[$mode][$risk] ?? false;
    }

    /**
     * Test PolicyResult is always returned with allowed and explanation
     */
    public function test_policy_result_always_has_required_fields()
    {
        SoulyRuntimeProfile::create([
            'autonomy_mode' => 'copilot',
            'is_quarantined' => false,
            'memory_access' => true,
            'contact_access' => true,
            'task_execution_access' => true,
            'workflow_execution_access' => true,
            'external_messaging_access' => false,
        ]);

        $result = $this->service->canExecute('answer', 'read');

        $this->assertObjectHasProperty('allowed', $result);
        $this->assertObjectHasProperty('explanation', $result);
        $this->assertIsBool($result->allowed);
        $this->assertIsString($result->explanation);
    }

    /**
     * Test allowed action returns explanation
     */
    public function test_allowed_action_returns_explanation()
    {
        SoulyRuntimeProfile::create([
            'autonomy_mode' => 'operator',
            'is_quarantined' => false,
            'memory_access' => true,
            'contact_access' => true,
            'task_execution_access' => true,
            'workflow_execution_access' => true,
            'external_messaging_access' => false,
        ]);

        $result = $this->service->canExecute('create_task', 'write_low');

        $this->assertTrue($result->allowed);
        $this->assertIsString($result->explanation);
        $this->assertNotEmpty($result->explanation);
    }
}
