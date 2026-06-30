<?php

namespace Tests\Unit\HedraSoul;

use Tests\TestCase;
use App\Models\HedrasoulMessage;
use App\Models\HedrasoulSession;
use App\Models\HedraMemorySuggestion;
use App\Models\HedraProfileFact;
use App\Services\HedraSoul\HedraMemoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * HedraMemoryServiceTest: Tests memory management and suggestions.
 * Validates: Requirements 11, 12, Correctness Properties 1-12
 */
class HedraMemoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HedraMemoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(HedraMemoryService::class);
    }

    /**
     * Test suggestFromMessage() creates pending suggestion
     */
    public function test_suggest_from_message_creates_pending()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'I prefer working in the mornings',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $suggestion = $this->service->suggestFromMessage($message);

        $this->assertInstanceOf(HedraMemorySuggestion::class, $suggestion);
        $this->assertEquals('pending', $suggestion->status);
        $this->assertEquals($message->id, $suggestion->source_message_id);
        $this->assertDatabaseHas('hedra_memory_suggestions', [
            'id' => $suggestion->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Test approve() sets status=approved and reviewed_at
     */
    public function test_approve_sets_approved_status()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Important preference',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $suggestion = $this->service->suggestFromMessage($message);

        $fact = $this->service->approve($suggestion);

        $suggestion->refresh();
        $this->assertEquals('approved', $suggestion->status);
        $this->assertNotNull($suggestion->reviewed_at);
        $this->assertInstanceOf(HedraProfileFact::class, $fact);
    }

    /**
     * Test approve() creates exactly one hedra_profile_facts record
     */
    public function test_approve_creates_single_fact()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'I like tea',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $suggestion = $this->service->suggestFromMessage($message);

        $initialFactCount = HedraProfileFact::count();
        $this->service->approve($suggestion);
        $finalFactCount = HedraProfileFact::count();

        $this->assertEquals($initialFactCount + 1, $finalFactCount);
    }

    /**
     * Test reject() sets status=rejected
     */
    public function test_reject_sets_rejected_status()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Random comment',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $suggestion = $this->service->suggestFromMessage($message);

        $this->service->reject($suggestion);

        $suggestion->refresh();
        $this->assertEquals('rejected', $suggestion->status);
        $this->assertNotNull($suggestion->reviewed_at);
    }

    /**
     * Test reject() creates zero hedra_profile_facts records
     */
    public function test_reject_creates_no_fact()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Random comment',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $suggestion = $this->service->suggestFromMessage($message);

        $initialFactCount = HedraProfileFact::count();
        $this->service->reject($suggestion);
        $finalFactCount = HedraProfileFact::count();

        $this->assertEquals($initialFactCount, $finalFactCount);
    }

    /**
     * Test createFact() creates a profile fact directly
     */
    public function test_create_fact_directly()
    {
        $data = [
            'memory_type' => 'preference',
            'content' => 'Prefers morning meetings',
            'confidence' => 0.9,
            'sensitivity' => 'internal',
            'visibility_scope' => 'private',
            'change_reason' => 'User specified',
        ];

        $fact = $this->service->createFact($data);

        $this->assertInstanceOf(HedraProfileFact::class, $fact);
        $this->assertTrue($fact->is_approved);
        $this->assertNotNull($fact->approved_at);
        $this->assertEquals(1, $fact->version);
        $this->assertDatabaseHas('hedra_profile_facts', [
            'id' => $fact->id,
            'memory_type' => 'preference',
            'is_approved' => true,
        ]);
    }

    /**
     * Test updateFact() creates version record
     */
    public function test_update_fact_creates_version()
    {
        $fact = HedraProfileFact::create([
            'memory_type' => 'preference',
            'content' => 'Original content',
            'confidence' => 0.8,
            'sensitivity' => 'internal',
            'visibility_scope' => 'private',
            'is_approved' => true,
            'approved_at' => now(),
            'version' => 1,
        ]);

        $initialVersionCount = $fact->versions()->count();

        $updated = $this->service->updateFact($fact, [
            'content' => 'Updated content',
            'change_reason' => 'Clarification',
        ]);

        $this->assertEquals('Updated content', $updated->content);
        $this->assertEquals(2, $updated->version);

        // Verify version record was created
        $finalVersionCount = HedraProfileFact::find($fact->id)->versions()->count();
        $this->assertGreaterThan($initialVersionCount, $finalVersionCount);
    }

    /**
     * Test deleteFact() removes fact
     */
    public function test_delete_fact()
    {
        $fact = HedraProfileFact::create([
            'memory_type' => 'preference',
            'content' => 'To be deleted',
            'confidence' => 0.8,
            'sensitivity' => 'internal',
            'visibility_scope' => 'private',
            'is_approved' => true,
            'approved_at' => now(),
            'version' => 1,
        ]);

        $factId = $fact->id;

        $this->service->deleteFact($fact);

        $this->assertNull(HedraProfileFact::find($factId));
    }

    /**
     * Test search() finds facts by content
     */
    public function test_search_finds_facts()
    {
        HedraProfileFact::create([
            'memory_type' => 'preference',
            'content' => 'I love coffee in the morning',
            'confidence' => 0.9,
            'sensitivity' => 'internal',
            'visibility_scope' => 'private',
            'is_approved' => true,
            'approved_at' => now(),
            'version' => 1,
        ]);

        $results = $this->service->search('coffee');

        $this->assertNotEmpty($results);
        $this->assertStringContainsString('coffee', json_encode($results));
    }

    /**
     * Test getPendingSuggestions() returns pending only
     */
    public function test_get_pending_suggestions()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        // Create pending suggestion
        $msg1 = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Pending memory',
            'body_format' => 'text',
            'status' => 'received',
        ]);
        $suggestion1 = $this->service->suggestFromMessage($msg1);

        // Create approved suggestion
        $msg2 = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Approved memory',
            'body_format' => 'text',
            'status' => 'received',
        ]);
        $suggestion2 = $this->service->suggestFromMessage($msg2);
        $this->service->approve($suggestion2);

        $pending = $this->service->getPendingSuggestions();

        $this->assertGreaterThanOrEqual(1, count($pending->items()));
        $pendingIds = array_map(fn($s) => $s['id'], $pending->items());
        $this->assertContains($suggestion1->id, $pendingIds);
    }

    /**
     * Test getFacts() returns approved facts
     */
    public function test_get_facts_returns_approved()
    {
        HedraProfileFact::create([
            'memory_type' => 'preference',
            'content' => 'Approved fact',
            'confidence' => 0.9,
            'sensitivity' => 'internal',
            'visibility_scope' => 'private',
            'is_approved' => true,
            'approved_at' => now(),
            'version' => 1,
        ]);

        $facts = $this->service->getFacts();

        $this->assertNotEmpty($facts->items());
    }

    /**
     * Test getFacts() with type filter
     */
    public function test_get_facts_with_type_filter()
    {
        HedraProfileFact::create([
            'memory_type' => 'preference',
            'content' => 'Preference fact',
            'confidence' => 0.9,
            'sensitivity' => 'internal',
            'visibility_scope' => 'private',
            'is_approved' => true,
            'approved_at' => now(),
            'version' => 1,
        ]);

        HedraProfileFact::create([
            'memory_type' => 'boundary',
            'content' => 'Boundary fact',
            'confidence' => 0.9,
            'sensitivity' => 'internal',
            'visibility_scope' => 'private',
            'is_approved' => true,
            'approved_at' => now(),
            'version' => 1,
        ]);

        $preferences = $this->service->getFacts('preference');

        foreach ($preferences->items() as $fact) {
            $this->assertEquals('preference', $fact['memory_type']);
        }
    }

    /**
     * PBT: For 100 random suggestions, verify facts only exist for approved ones
     */
    public function test_pbt_100_random_suggestions_facts_only_for_approved()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        for ($i = 0; $i < 100; $i++) {
            $message = HedrasoulMessage::create([
                'session_id' => $session->id,
                'sender_type' => 'user',
                'body' => "Memory suggestion $i",
                'body_format' => 'text',
                'status' => 'received',
            ]);

            $suggestion = $this->service->suggestFromMessage($message);

            // Randomly approve or reject
            $shouldApprove = rand(0, 1) === 1;

            $factCountBefore = HedraProfileFact::where(
                'content',
                'like',
                "%Memory suggestion $i%"
            )->count();

            if ($shouldApprove) {
                $this->service->approve($suggestion);
            } else {
                $this->service->reject($suggestion);
            }

            $factCountAfter = HedraProfileFact::where(
                'content',
                'like',
                "%Memory suggestion $i%"
            )->count();

            if ($shouldApprove) {
                $this->assertGreaterThan(
                    $factCountBefore,
                    $factCountAfter,
                    "Iteration $i: Approved suggestion should create a fact"
                );
            } else {
                $this->assertEquals(
                    $factCountBefore,
                    $factCountAfter,
                    "Iteration $i: Rejected suggestion should not create a fact"
                );
            }
        }
    }

    /**
     * Test memory type classification
     */
    public function test_memory_type_classification()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        $testCases = [
            'I like morning meetings' => 'preference',
            'I value honesty and integrity' => 'semantic',
            'I never want to work weekends' => 'boundary',
            'My tone is professional but friendly' => 'tone_style',
        ];

        foreach ($testCases as $body => $expectedType) {
            $message = HedrasoulMessage::create([
                'session_id' => $session->id,
                'sender_type' => 'user',
                'body' => $body,
                'body_format' => 'text',
                'status' => 'received',
            ]);

            $suggestion = $this->service->suggestFromMessage($message);

            $this->assertEquals(
                $expectedType,
                $suggestion->memory_type,
                "Failed for: $body"
            );
        }
    }

    /**
     * Test approve() creates version record
     */
    public function test_approve_creates_version_record()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        $message = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Important memory',
            'body_format' => 'text',
            'status' => 'received',
        ]);

        $suggestion = $this->service->suggestFromMessage($message);
        $fact = $this->service->approve($suggestion);

        // Verify version record exists
        $versions = $fact->versions()->get();
        $this->assertGreaterThanOrEqual(1, count($versions));
    }

    /**
     * Test multiple suggestion cycles
     */
    public function test_multiple_suggestion_cycles()
    {
        $session = HedrasoulSession::factory()->create(['status' => 'active']);

        // Create and approve
        $msg1 = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'First memory',
            'body_format' => 'text',
            'status' => 'received',
        ]);
        $sugg1 = $this->service->suggestFromMessage($msg1);
        $this->service->approve($sugg1);

        // Create and reject
        $msg2 = HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Second memory',
            'body_format' => 'text',
            'status' => 'received',
        ]);
        $sugg2 = $this->service->suggestFromMessage($msg2);
        $this->service->reject($sugg2);

        // Verify facts created only for approved
        $facts = HedraProfileFact::all();
        $this->assertGreaterThanOrEqual(1, count($facts));

        $sugg1->refresh();
        $sugg2->refresh();

        $this->assertEquals('approved', $sugg1->status);
        $this->assertEquals('rejected', $sugg2->status);
    }
}
