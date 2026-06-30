<?php

namespace Tests\Feature\HedraSoul;

use Tests\TestCase;
use App\Models\SoulyRuntimeProfile;
use App\Models\AiInstance;
use App\Models\User;
use App\Events\HedraSoul\HedraSoulAutonomyChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class HedraSoulSoulyControlApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test POST /hedrasoul/souly/quarantine sets is_quarantined=true on runtime profile, returns 200
     */
    public function test_post_quarantine_sets_quarantine_true_returns_200()
    {
        $profile = SoulyRuntimeProfile::factory()->create([
            'is_quarantined' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/hedrasoul/souly/quarantine');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'quarantined');

        // Verify profile is quarantined in database
        $this->assertTrue(SoulyRuntimeProfile::first()->is_quarantined);
    }

    /**
     * Test POST /hedrasoul/souly/resume sets is_quarantined=false, returns 200
     */
    public function test_post_resume_sets_quarantine_false_returns_200()
    {
        $profile = SoulyRuntimeProfile::factory()->create([
            'is_quarantined' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/hedrasoul/souly/resume');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'resumed');

        // Verify profile is not quarantined in database
        $this->assertFalse(SoulyRuntimeProfile::first()->is_quarantined);
    }

    /**
     * Test PATCH /hedrasoul/souly/autonomy updates autonomy_mode
     */
    public function test_patch_autonomy_updates_mode()
    {
        $profile = SoulyRuntimeProfile::factory()->create([
            'autonomy_mode' => 'chat_only',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson('/api/v1/hedrasoul/souly/autonomy', [
                'autonomy_mode' => 'copilot',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('autonomy_mode', 'copilot');

        $this->assertDatabaseHas('souly_runtime_profiles', [
            'autonomy_mode' => 'copilot',
        ]);
    }

    /**
     * Test PATCH /hedrasoul/souly/autonomy broadcasts HedraSoulAutonomyChanged event
     */
    public function test_patch_autonomy_broadcasts_event()
    {
        Event::fake();

        $profile = SoulyRuntimeProfile::factory()->create([
            'autonomy_mode' => 'chat_only',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson('/api/v1/hedrasoul/souly/autonomy', [
                'autonomy_mode' => 'copilot',
            ]);

        Event::assertDispatched(HedraSoulAutonomyChanged::class);
    }

    /**
     * Test PATCH /hedrasoul/souly/model with valid model_instance_id updates profile, returns 200
     */
    public function test_patch_model_with_valid_id_updates_and_returns_200()
    {
        $profile = SoulyRuntimeProfile::factory()->create();
        $aiInstance = AiInstance::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson('/api/v1/hedrasoul/souly/model', [
                'model_instance_id' => $aiInstance->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('active_model_instance_id', $aiInstance->id);

        $this->assertDatabaseHas('souly_runtime_profiles', [
            'active_model_instance_id' => $aiInstance->id,
        ]);
    }

    /**
     * Test PATCH /hedrasoul/souly/model with invalid model_instance_id returns 422
     */
    public function test_patch_model_with_invalid_id_returns_422()
    {
        $profile = SoulyRuntimeProfile::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson('/api/v1/hedrasoul/souly/model', [
                'model_instance_id' => 99999,
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test GET /hedrasoul/souly/status returns runtime profile, returns 200
     */
    public function test_get_status_returns_runtime_profile_200()
    {
        $profile = SoulyRuntimeProfile::factory()->create([
            'autonomy_mode' => 'copilot',
            'is_quarantined' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/hedrasoul/souly/status');

        $response->assertStatus(200);
        $response->assertJsonPath('autonomy_mode', 'copilot');
        $response->assertJsonPath('is_quarantined', false);
    }

    /**
     * Test POST /hedrasoul/souly/simulate returns response without executing side effects
     */
    public function test_post_simulate_returns_result_without_side_effects()
    {
        $profile = SoulyRuntimeProfile::factory()->create([
            'autonomy_mode' => 'chat_only',
        ]);

        // Create a session for the simulate endpoint
        $session = \App\Models\HedrasoulSession::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/hedrasoul/souly/simulate', [
                'body' => 'What is the weather?',
                'session_id' => $session->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'intent',
            'risk_level',
            'policy_result',
            'would_execute',
        ]);
    }

    /**
     * Test POST /hedrasoul/souly/quarantine returns 401 without authentication
     */
    public function test_post_quarantine_returns_401_without_auth()
    {
        $response = $this->postJson('/api/v1/hedrasoul/souly/quarantine');

        $response->assertStatus(401);
    }

    /**
     * Test POST /hedrasoul/souly/resume returns 401 without authentication
     */
    public function test_post_resume_returns_401_without_auth()
    {
        $response = $this->postJson('/api/v1/hedrasoul/souly/resume');

        $response->assertStatus(401);
    }

    /**
     * Test PATCH /hedrasoul/souly/autonomy returns 401 without authentication
     */
    public function test_patch_autonomy_returns_401_without_auth()
    {
        $response = $this->patchJson('/api/v1/hedrasoul/souly/autonomy', [
            'autonomy_mode' => 'copilot',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test PATCH /hedrasoul/souly/model returns 401 without authentication
     */
    public function test_patch_model_returns_401_without_auth()
    {
        $response = $this->patchJson('/api/v1/hedrasoul/souly/model', [
            'model_instance_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test GET /hedrasoul/souly/status returns 401 without authentication
     */
    public function test_get_status_returns_401_without_auth()
    {
        $response = $this->getJson('/api/v1/hedrasoul/souly/status');

        $response->assertStatus(401);
    }

    /**
     * Test POST /hedrasoul/souly/simulate returns 401 without authentication
     */
    public function test_post_simulate_returns_401_without_auth()
    {
        $response = $this->postJson('/api/v1/hedrasoul/souly/simulate', [
            'body' => 'Test message',
            'session_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test PATCH /hedrasoul/souly/autonomy with invalid mode returns 422
     */
    public function test_patch_autonomy_with_invalid_mode_returns_422()
    {
        $profile = SoulyRuntimeProfile::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson('/api/v1/hedrasoul/souly/autonomy', [
                'autonomy_mode' => 'invalid_mode',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test PATCH /hedrasoul/souly/autonomy accepts all valid modes
     */
    public function test_patch_autonomy_accepts_all_valid_modes()
    {
        $validModes = ['chat_only', 'copilot', 'operator', 'autopilot_limited', 'emergency_paused'];

        foreach ($validModes as $mode) {
            $profile = SoulyRuntimeProfile::factory()->create([
                'autonomy_mode' => 'chat_only',
            ]);

            $response = $this->actingAs($this->user, 'sanctum')
                ->patchJson('/api/v1/hedrasoul/souly/autonomy', [
                    'autonomy_mode' => $mode,
                ]);

            $response->assertStatus(200);
            $response->assertJsonPath('autonomy_mode', $mode);
        }
    }
}
