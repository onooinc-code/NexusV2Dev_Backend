<?php

namespace Tests\Feature\HedraSoul;

use Tests\TestCase;
use App\Models\HedraProfileFact;
use App\Models\HedraMemorySuggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HedraSoulMemoriesApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test POST /hedrasoul/memories/{id}/approve sets suggestion status='approved'
     */
    public function test_post_memory_approve_sets_approved_status()
    {
        $suggestion = HedraMemorySuggestion::factory()->create([
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/memories/{$suggestion->id}/approve");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'approved');

        $this->assertDatabaseHas('hedra_memory_suggestions', [
            'id' => $suggestion->id,
            'status' => 'approved',
        ]);
    }

    /**
     * Test POST /hedrasoul/memories/{id}/approve creates hedra_profile_facts record
     */
    public function test_post_memory_approve_creates_profile_fact()
    {
        $suggestion = HedraMemorySuggestion::factory()->create([
            'status' => 'pending',
            'content' => 'Hedra likes coffee',
            'memory_type' => 'preference',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/memories/{$suggestion->id}/approve");

        $this->assertDatabaseHas('hedra_profile_facts', [
            'content' => 'Hedra likes coffee',
            'memory_type' => 'preference',
            'is_approved' => true,
        ]);
    }

    /**
     * Test POST /hedrasoul/memories/{id}/reject sets suggestion status='rejected'
     */
    public function test_post_memory_reject_sets_rejected_status()
    {
        $suggestion = HedraMemorySuggestion::factory()->create([
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/memories/{$suggestion->id}/reject");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'rejected');

        $this->assertDatabaseHas('hedra_memory_suggestions', [
            'id' => $suggestion->id,
            'status' => 'rejected',
        ]);
    }

    /**
     * Test POST /hedrasoul/memories/{id}/reject creates no fact record
     */
    public function test_post_memory_reject_creates_no_fact()
    {
        $suggestion = HedraMemorySuggestion::factory()->create([
            'status' => 'pending',
            'content' => 'Some memory suggestion',
        ]);

        $factCountBefore = HedraProfileFact::count();

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/memories/{$suggestion->id}/reject");

        $factCountAfter = HedraProfileFact::count();
        $this->assertEquals($factCountBefore, $factCountAfter);
    }

    /**
     * Test GET /hedrasoul/memories returns combined facts and suggestions
     */
    public function test_get_memories_returns_facts_and_suggestions()
    {
        HedraProfileFact::factory(3)->create();
        HedraMemorySuggestion::factory(2)->create(['status' => 'pending']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/hedrasoul/memories');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'facts',
            'pending_suggestions',
        ]);
    }

    /**
     * Test GET /hedrasoul/memories supports optional ?status= filtering
     */
    public function test_get_memories_supports_status_filter()
    {
        HedraProfileFact::factory(3)->create();
        HedraMemorySuggestion::factory(2)->create(['status' => 'pending']);
        HedraMemorySuggestion::factory(1)->create(['status' => 'approved']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/hedrasoul/memories?status=pending');

        $response->assertStatus(200);
    }

    /**
     * Test GET /hedrasoul/memories supports optional ?type= filtering
     */
    public function test_get_memories_supports_type_filter()
    {
        HedraProfileFact::factory(2)->create(['memory_type' => 'preference']);
        HedraProfileFact::factory(1)->create(['memory_type' => 'boundary']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/hedrasoul/memories?type=preference');

        $response->assertStatus(200);
    }

    /**
     * Test POST /hedrasoul/memories creates new hedra_profile_facts record directly, returns 201
     */
    public function test_post_memory_creates_fact_directly_and_returns_201()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/hedrasoul/memories', [
                'memory_type' => 'preference',
                'content' => 'Hedra prefers email communication',
                'confidence' => 0.95,
                'sensitivity' => 'internal',
                'visibility_scope' => 'personal',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('memory_type', 'preference');
        $response->assertJsonPath('content', 'Hedra prefers email communication');

        $this->assertDatabaseHas('hedra_profile_facts', [
            'memory_type' => 'preference',
            'content' => 'Hedra prefers email communication',
            'is_approved' => true,
        ]);
    }

    /**
     * Test PATCH /hedrasoul/memories/{id} updates memory
     */
    public function test_patch_memory_updates_fact()
    {
        $fact = HedraProfileFact::factory()->create([
            'content' => 'Old content',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/hedrasoul/memories/{$fact->id}", [
                'content' => 'Updated content',
                'confidence' => 0.9,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hedra_profile_facts', [
            'id' => $fact->id,
            'content' => 'Updated content',
        ]);
    }

    /**
     * Test POST /hedrasoul/memory-maintenance with rebuild_embeddings action
     */
    public function test_post_memory_maintenance_rebuild_embeddings()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/hedrasoul/memory-maintenance', [
                'action' => 'rebuild_embeddings',
            ]);

        $response->assertStatus(202);
    }

    /**
     * Test POST /hedrasoul/memory-maintenance with prune_stale action
     */
    public function test_post_memory_maintenance_prune_stale()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/hedrasoul/memory-maintenance', [
                'action' => 'prune_stale',
            ]);

        $response->assertStatus(202);
    }

    /**
     * Test POST /hedrasoul/memories/{id}/approve returns 401 without authentication
     */
    public function test_post_memory_approve_returns_401_without_auth()
    {
        $suggestion = HedraMemorySuggestion::factory()->create();

        $response = $this->postJson("/api/v1/hedrasoul/memories/{$suggestion->id}/approve");

        $response->assertStatus(401);
    }

    /**
     * Test POST /hedrasoul/memories/{id}/reject returns 401 without authentication
     */
    public function test_post_memory_reject_returns_401_without_auth()
    {
        $suggestion = HedraMemorySuggestion::factory()->create();

        $response = $this->postJson("/api/v1/hedrasoul/memories/{$suggestion->id}/reject");

        $response->assertStatus(401);
    }

    /**
     * Test GET /hedrasoul/memories returns 401 without authentication
     */
    public function test_get_memories_returns_401_without_auth()
    {
        $response = $this->getJson('/api/v1/hedrasoul/memories');

        $response->assertStatus(401);
    }

    /**
     * Test POST /hedrasoul/memories returns 401 without authentication
     */
    public function test_post_memory_returns_401_without_auth()
    {
        $response = $this->postJson('/api/v1/hedrasoul/memories', [
            'memory_type' => 'preference',
            'content' => 'Test',
            'confidence' => 0.9,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test PATCH /hedrasoul/memories/{id} returns 401 without authentication
     */
    public function test_patch_memory_returns_401_without_auth()
    {
        $fact = HedraProfileFact::factory()->create();

        $response = $this->patchJson("/api/v1/hedrasoul/memories/{$fact->id}", [
            'content' => 'New content',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test POST /hedrasoul/memory-maintenance returns 401 without authentication
     */
    public function test_post_memory_maintenance_returns_401_without_auth()
    {
        $response = $this->postJson('/api/v1/hedrasoul/memory-maintenance', [
            'action' => 'rebuild_embeddings',
        ]);

        $response->assertStatus(401);
    }
}
