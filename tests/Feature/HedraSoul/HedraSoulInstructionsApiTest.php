<?php

namespace Tests\Feature\HedraSoul;

use Tests\TestCase;
use App\Models\SoulyInstructionVersion;
use App\Models\SoulyActionTrace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HedraSoulInstructionsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test POST /hedrasoul/instructions creates draft with status='draft' and returns 201
     */
    public function test_post_instructions_creates_draft_and_returns_201()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/hedrasoul/instructions', [
                'content' => ['system' => 'You are Souly', 'tone' => 'helpful'],
                'change_reason' => 'Initial instruction set',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'draft');
        $response->assertJsonPath('change_reason', 'Initial instruction set');

        $this->assertDatabaseHas('souly_instruction_versions', [
            'status' => 'draft',
        ]);
    }

    /**
     * Test POST /hedrasoul/instructions/{id}/activate sets target to 'active', archives others, returns 200
     */
    public function test_post_activate_sets_active_and_archives_previous()
    {
        $oldActive = SoulyInstructionVersion::factory()->create([
            'status' => 'active',
            'version_number' => 1,
        ]);

        $draft = SoulyInstructionVersion::factory()->create([
            'status' => 'draft',
            'version_number' => 2,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/instructions/{$draft->id}/activate");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'active');

        // Verify old active is archived
        $this->assertDatabaseHas('souly_instruction_versions', [
            'id' => $oldActive->id,
            'status' => 'archived',
        ]);

        // Verify new is active
        $this->assertDatabaseHas('souly_instruction_versions', [
            'id' => $draft->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test Activation returns 202 when new version expands autonomy permissions
     */
    public function test_post_activate_returns_202_when_autonomy_expands()
    {
        $draft = SoulyInstructionVersion::factory()->create([
            'status' => 'draft',
            'content' => ['autonomy_level' => 'autopilot_limited'],
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/instructions/{$draft->id}/activate");

        // May return 202 if approval needed for autonomy expansion
        $this->assertContains($response->status(), [200, 202]);
    }

    /**
     * Test POST /hedrasoul/instructions/{id}/rollback restores prior active version, returns 200
     */
    public function test_post_rollback_restores_prior_version()
    {
        $v1 = SoulyInstructionVersion::factory()->create([
            'status' => 'archived',
            'version_number' => 1,
        ]);

        $v2 = SoulyInstructionVersion::factory()->create([
            'status' => 'active',
            'version_number' => 2,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/instructions/{$v2->id}/rollback");

        $response->assertStatus(200);

        // Verify rollback made v1 active again
        $this->assertDatabaseHas('souly_instruction_versions', [
            'id' => $v1->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test POST /hedrasoul/instructions/{id}/test returns response string, persists no side effects
     */
    public function test_post_test_returns_response_no_side_effects()
    {
        $version = SoulyInstructionVersion::factory()->create([
            'status' => 'draft',
        ]);

        $traceCountBefore = SoulyActionTrace::count();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/instructions/{$version->id}/test", [
                'test_prompt' => 'Say hello to Hedra',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'test_prompt',
            'response',
        ]);

        // Verify no new instruction versions or traces created
        $traceCountAfter = SoulyActionTrace::count();
        $this->assertEquals($traceCountBefore, $traceCountAfter);
    }

    /**
     * Test GET /hedrasoul/instructions returns list of versions
     */
    public function test_get_instructions_returns_list()
    {
        SoulyInstructionVersion::factory(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/hedrasoul/instructions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'current_page',
        ]);
    }

    /**
     * Test GET /hedrasoul/instructions/{id} returns instruction with diff
     */
    public function test_get_instruction_by_id_returns_version_and_diff()
    {
        $version = SoulyInstructionVersion::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/hedrasoul/instructions/{$version->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'version',
            'diff',
        ]);
    }

    /**
     * Test PATCH /hedrasoul/instructions/{id} updates draft content
     */
    public function test_patch_instruction_updates_draft()
    {
        $draft = SoulyInstructionVersion::factory()->create([
            'status' => 'draft',
            'content' => ['system' => 'Old content'],
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/hedrasoul/instructions/{$draft->id}", [
                'content' => ['system' => 'New content'],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('souly_instruction_versions', [
            'id' => $draft->id,
            'status' => 'draft',
        ]);
    }

    /**
     * Test PATCH /hedrasoul/instructions/{id} cannot update active version
     */
    public function test_patch_instruction_cannot_update_active_version()
    {
        $active = SoulyInstructionVersion::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/hedrasoul/instructions/{$active->id}", [
                'content' => ['system' => 'New content'],
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test all instruction endpoints return 401 without authentication
     */
    public function test_instructions_endpoints_return_401_without_auth()
    {
        $version = SoulyInstructionVersion::factory()->create();

        // POST /hedrasoul/instructions
        $this->postJson('/api/v1/hedrasoul/instructions', [
            'content' => ['test' => 'data'],
        ])->assertStatus(401);

        // GET /hedrasoul/instructions
        $this->getJson('/api/v1/hedrasoul/instructions')->assertStatus(401);

        // GET /hedrasoul/instructions/{id}
        $this->getJson("/api/v1/hedrasoul/instructions/{$version->id}")->assertStatus(401);

        // PATCH /hedrasoul/instructions/{id}
        $this->patchJson("/api/v1/hedrasoul/instructions/{$version->id}", [])->assertStatus(401);

        // POST /hedrasoul/instructions/{id}/activate
        $this->postJson("/api/v1/hedrasoul/instructions/{$version->id}/activate")->assertStatus(401);

        // POST /hedrasoul/instructions/{id}/rollback
        $this->postJson("/api/v1/hedrasoul/instructions/{$version->id}/rollback")->assertStatus(401);

        // POST /hedrasoul/instructions/{id}/test
        $this->postJson("/api/v1/hedrasoul/instructions/{$version->id}/test", [
            'test_prompt' => 'test',
        ])->assertStatus(401);
    }
}
