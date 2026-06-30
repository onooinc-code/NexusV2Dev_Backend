<?php

namespace Tests\Unit\HedraSoul;

use Tests\TestCase;
use App\Models\HedrasoulMessage;
use App\Models\HedrasoulSession;
use App\Models\SoulyRuntimeProfile;
use App\Services\HedraSoul\SoulyCommandRouter;
use App\Services\HedraSoul\SoulyActionPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * SoulyCommandRouterTest: Tests the classify() method for all 11 intent types.
 * Validates: Requirements 5, Correctness Properties 1-12
 */
class SoulyCommandRouterTest extends TestCase
{
    use RefreshDatabase;

    protected SoulyCommandRouter $router;
    protected SoulyActionPolicyService $policyService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create runtime profile for testing
        SoulyRuntimeProfile::create([
            'autonomy_mode' => 'copilot',
            'is_quarantined' => false,
            'memory_access' => true,
            'contact_access' => true,
            'task_execution_access' => true,
            'workflow_execution_access' => true,
            'external_messaging_access' => false,
        ]);

        $this->router = app(SoulyCommandRouter::class);
        $this->policyService = app(SoulyActionPolicyService::class);
    }

    /**
     * Test plain question returns 'answer' intent
     */
    public function test_plain_question_returns_answer_intent()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'What is the capital of France?',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $intent = $this->router->classify($message);

        $this->assertEquals('answer', $intent->intent);
        $this->assertEquals('read', $intent->riskLevel);
        $this->assertNotNull($intent->policyResult);
        $this->assertTrue($intent->policyResult->allowed);
    }

    /**
     * Test draft intent classification
     */
    public function test_draft_intent_classification()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => '/draft this email for my client',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $intent = $this->router->classify($message);

        $this->assertEquals('draft', $intent->intent);
        $this->assertEquals('draft', $intent->riskLevel);
    }

    /**
     * Test create_task intent classification
     */
    public function test_create_task_intent_classification()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => '/task Create an analysis of Q3 results',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $intent = $this->router->classify($message);

        $this->assertEquals('create_task', $intent->intent);
        $this->assertEquals('write_low', $intent->riskLevel);
    }

    /**
     * Test execute_agent intent classification
     */
    public function test_execute_agent_intent_classification()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => '/agent execute the CRM sync workflow',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $intent = $this->router->classify($message);

        $this->assertEquals('execute_agent', $intent->intent);
        $this->assertEquals('write_medium', $intent->riskLevel);
    }

    /**
     * Test start_workflow intent classification
     */
    public function test_start_workflow_intent_classification()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => '/workflow start the quarterly review process',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $intent = $this->router->classify($message);

        $this->assertEquals('start_workflow', $intent->intent);
        $this->assertEquals('write_medium', $intent->riskLevel);
    }

    /**
     * Test schedule_work intent classification
     */
    public function test_schedule_work_intent_classification()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'schedule work for next Friday on the report',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $intent = $this->router->classify($message);

        $this->assertEquals('schedule_work', $intent->intent);
        $this->assertEquals('write_low', $intent->riskLevel);
    }

    /**
     * Test open_approval intent classification
     */
    public function test_open_approval_intent_classification()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'This needs approval from the director',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $intent = $this->router->classify($message);

        $this->assertEquals('open_approval', $intent->intent);
        $this->assertEquals('read', $intent->riskLevel);
    }

    /**
     * Test update_profile intent classification
     */
    public function test_update_profile_intent_classification()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => '/profile update my contact email',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $intent = $this->router->classify($message);

        $this->assertEquals('update_profile', $intent->intent);
        $this->assertEquals('write_low', $intent->riskLevel);
    }

    /**
     * Test suggest_memory intent classification
     */
    public function test_suggest_memory_intent_classification()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => '/memory remember I prefer morning meetings',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $intent = $this->router->classify($message);

        $this->assertEquals('suggest_memory', $intent->intent);
        $this->assertEquals('write_low', $intent->riskLevel);
    }

    /**
     * Test suggest_setting intent classification
     */
    public function test_suggest_setting_intent_classification()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => '/settings change the notification frequency to daily',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $intent = $this->router->classify($message);

        $this->assertEquals('suggest_setting', $intent->intent);
        $this->assertEquals('write_low', $intent->riskLevel);
    }

    /**
     * Test notify intent classification
     */
    public function test_notify_intent_classification()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => '/notify Send an alert to the team about the update',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $intent = $this->router->classify($message);

        $this->assertEquals('notify', $intent->intent);
        $this->assertEquals('external_send', $intent->riskLevel);
    }

    /**
     * Test risk_level is correctly set per intent type
     */
    public function test_risk_levels_match_intent_types()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $testCases = [
            ['answer', 'read'],
            ['draft', 'draft'],
            ['create_task', 'write_low'],
            ['execute_agent', 'write_medium'],
            ['start_workflow', 'write_medium'],
            ['schedule_work', 'write_low'],
            ['open_approval', 'read'],
            ['update_profile', 'write_low'],
            ['suggest_memory', 'write_low'],
            ['suggest_setting', 'write_low'],
            ['notify', 'external_send'],
        ];

        foreach ($testCases as [$expectedIntent, $expectedRisk]) {
            // Create message that matches this intent
            $body = match($expectedIntent) {
                'answer' => 'What is your name?',
                'draft' => '/draft write this down',
                'create_task' => '/task do something',
                'execute_agent' => '/agent run it',
                'start_workflow' => '/workflow begin',
                'schedule_work' => 'schedule work for me',
                'open_approval' => 'needs approval',
                'update_profile' => '/profile update it',
                'suggest_memory' => '/memory remember this',
                'suggest_setting' => '/settings change it',
                'notify' => '/notify tell everyone',
            };

            $message = HedrasoulMessage::create([
                'session_id' => $session->id,
                'sender_type' => 'user',
                'body' => $body,
                'body_format' => 'text',
                'status' => 'received',
            ]);

            $intent = $this->router->classify($message);

            $this->assertEquals($expectedIntent, $intent->intent, 
                "Intent mismatch for body: $body");
            $this->assertEquals($expectedRisk, $intent->riskLevel, 
                "Risk level mismatch for intent: $expectedIntent");
        }
    }

    /**
     * Test policy service is invoked and result included in CommandIntent
     */
    public function test_policy_service_invoked_and_result_included()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'What is the answer?',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $intent = $this->router->classify($message);

        // Verify policy result is present
        $this->assertNotNull($intent->policyResult);
        $this->assertIsBool($intent->policyResult->allowed);
        $this->assertIsString($intent->policyResult->explanation);
        $this->assertTrue($intent->policyResult->allowed);
        $this->assertNotEmpty($intent->policyResult->explanation);
    }

    /**
     * Test CommandIntent holds all required data
     */
    public function test_command_intent_contains_all_required_fields()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);
        
        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => '/task Create something important',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $intent = $this->router->classify($message);

        // All fields must be present
        $this->assertIsString($intent->intent);
        $this->assertIsString($intent->riskLevel);
        $this->assertNotNull($intent->policyResult);
        $this->assertObjectHasProperty('allowed', $intent->policyResult);
        $this->assertObjectHasProperty('explanation', $intent->policyResult);
    }
}
