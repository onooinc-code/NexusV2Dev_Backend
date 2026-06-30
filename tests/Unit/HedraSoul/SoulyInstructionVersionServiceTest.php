<?php

namespace Tests\Unit\HedraSoul;

use Tests\TestCase;
use App\Models\SoulyInstructionVersion;
use App\Models\SoulyRuntimeProfile;
use App\Services\HedraSoul\SoulyInstructionVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * SoulyInstructionVersionServiceTest: Tests instruction versioning and lifecycle.
 * Validates: Requirements 6, Correctness Properties 1-12
 */
class SoulyInstructionVersionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SoulyInstructionVersionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed user 123 so that activated_by FK (souly_instruction_versions → users) is satisfied
        \DB::table('users')->insert([
            'id'                => 123,
            'name'              => 'Test User',
            'email'             => 'testuser@nexus.test',
            'password'          => bcrypt('password'),
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // Create runtime profile
        SoulyRuntimeProfile::create([
            'autonomy_mode' => 'copilot',
            'is_quarantined' => false,
            'memory_access' => true,
            'contact_access' => true,
            'task_execution_access' => true,
            'workflow_execution_access' => true,
            'external_messaging_access' => false,
        ]);

        $this->service = app(SoulyInstructionVersionService::class);
    }

    /**
     * Test createDraft() produces record with status=draft
     */
    public function test_create_draft_produces_draft_status()
    {
        $content = ['instructions' => 'Be helpful'];
        $reason = 'Initial version';

        $version = $this->service->createDraft($content, $reason);

        $this->assertInstanceOf(SoulyInstructionVersion::class, $version);
        $this->assertEquals('draft', $version->status);
        $this->assertEquals(1, $version->version_number);
        $this->assertDatabaseHas('souly_instruction_versions', [
            'id' => $version->id,
            'status' => 'draft',
            'version_number' => 1,
        ]);
    }

    /**
     * Test createDraft() increments version_number sequentially
     */
    public function test_create_draft_increments_version_number()
    {
        $v1 = $this->service->createDraft(['v' => 1], 'First');
        $this->assertEquals(1, $v1->version_number);

        $v2 = $this->service->createDraft(['v' => 2], 'Second');
        $this->assertEquals(2, $v2->version_number);

        $v3 = $this->service->createDraft(['v' => 3], 'Third');
        $this->assertEquals(3, $v3->version_number);
    }

    /**
     * Test activate() sets target version to active
     */
    public function test_activate_sets_version_to_active()
    {
        $version = $this->service->createDraft(['v' => 1], 'First');

        $this->service->activate($version, 123);

        $version->refresh();
        $this->assertEquals('active', $version->status);
        $this->assertNotNull($version->activated_at);
        $this->assertEquals(123, $version->activated_by);
    }

    /**
     * Test activate() archives all previous active versions
     */
    public function test_activate_archives_previous_active()
    {
        // Create and activate v1
        $v1 = $this->service->createDraft(['v' => 1], 'First');
        $this->service->activate($v1, 123);

        // Create and activate v2
        $v2 = $this->service->createDraft(['v' => 2], 'Second');
        $this->service->activate($v2, 123);

        // v1 should now be archived
        $v1->refresh();
        $this->assertEquals('archived', $v1->status);

        // v2 should be active
        $v2->refresh();
        $this->assertEquals('active', $v2->status);

        // Verify only one active version
        $activeCount = SoulyInstructionVersion::where('status', 'active')->count();
        $this->assertEquals(1, $activeCount);
    }

    /**
     * Test rollback() activates prior version
     */
    public function test_rollback_activates_prior_version()
    {
        $v1 = $this->service->createDraft(['v' => 1], 'First');
        $this->service->activate($v1, 123);

        $v2 = $this->service->createDraft(['v' => 2], 'Second');
        $this->service->activate($v2, 123);

        // Rollback from v2 to v1
        $this->service->rollback($v2);

        $v1->refresh();
        $v2->refresh();

        $this->assertEquals('active', $v1->status);
        $this->assertEquals('archived', $v2->status);
    }

    /**
     * Test testSandbox() returns response string
     */
    public function test_test_sandbox_returns_response()
    {
        $version = $this->service->createDraft(['v' => 1], 'Test');

        $response = $this->service->testSandbox($version, 'What is 2+2?');

        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }

    /**
     * Test testSandbox() persists NO side effects
     */
    public function test_test_sandbox_persists_no_side_effects()
    {
        $version = $this->service->createDraft(['v' => 1], 'Test');

        $initialCount = SoulyInstructionVersion::count();

        $response = $this->service->testSandbox($version, 'What is 2+2?');

        // No new versions should be created
        $finalCount = SoulyInstructionVersion::count();
        $this->assertEquals($initialCount, $finalCount);
    }

    /**
     * PBT: Run activate() 50 times and assert exactly one active version after each
     */
    public function test_pbt_activate_maintains_single_active_version()
    {
        for ($i = 0; $i < 50; $i++) {
            $version = $this->service->createDraft(['iteration' => $i], "Iteration $i");
            $this->service->activate($version, 123);

            // Assert exactly one active version
            $activeCount = SoulyInstructionVersion::where('status', 'active')->count();
            $this->assertEquals(
                1,
                $activeCount,
                "After iteration $i: Expected exactly 1 active version, found $activeCount"
            );

            // All others should be archived or draft
            $invalidStatuses = SoulyInstructionVersion::whereNotIn('status', ['active', 'archived'])
                ->count();
            $this->assertEquals(0, $invalidStatuses, 
                "After iteration $i: Found versions with invalid status");
        }
    }

    /**
     * Test diff() returns array with version info
     */
    public function test_diff_returns_version_info()
    {
        $v1 = $this->service->createDraft(['instruction' => 'Be helpful'], 'First');
        $this->service->activate($v1, 123);

        $v2 = $this->service->createDraft(['instruction' => 'Be helpful and concise'], 'Second');

        $diff = $this->service->diff($v2->id);

        $this->assertArrayHasKey('target_version_id', $diff);
        $this->assertArrayHasKey('target_version_number', $diff);
        $this->assertArrayHasKey('active_version_id', $diff);
        $this->assertArrayHasKey('active_version_number', $diff);
        $this->assertArrayHasKey('changes', $diff);
        $this->assertArrayHasKey('total_changes', $diff);
    }

    /**
     * Test getVersions() returns all versions
     */
    public function test_get_versions_returns_all()
    {
        $v1 = $this->service->createDraft(['v' => 1], 'First');
        $this->service->activate($v1, 123);

        $v2 = $this->service->createDraft(['v' => 2], 'Second');

        $versions = $this->service->getVersions();

        $this->assertGreaterThanOrEqual(2, count($versions));
    }

    /**
     * Test getVersions() with status filter
     */
    public function test_get_versions_with_status_filter()
    {
        $v1 = $this->service->createDraft(['v' => 1], 'First');
        $this->service->activate($v1, 123);

        $v2 = $this->service->createDraft(['v' => 2], 'Second');

        $drafts = $this->service->getVersions('draft');
        $active = $this->service->getVersions('active');

        $this->assertGreaterThan(0, count($drafts));
        $this->assertEquals(1, count($active));
    }

    /**
     * Test getActive() returns currently active version
     */
    public function test_get_active_returns_current_active()
    {
        $v1 = $this->service->createDraft(['v' => 1], 'First');
        $this->service->activate($v1, 123);

        $active = $this->service->getActive();

        $this->assertNotNull($active);
        $this->assertEquals('active', $active->status);
        $this->assertEquals($v1->id, $active->id);
    }

    /**
     * Test activate() updates runtime profile
     */
    public function test_activate_updates_runtime_profile()
    {
        $version = $this->service->createDraft(['v' => 1], 'First');
        $this->service->activate($version, 123);

        $profile = SoulyRuntimeProfile::first();
        $this->assertEquals($version->id, $profile->active_instruction_version_id);
    }

    /**
     * Test rollback() with multiple versions
     */
    public function test_rollback_complex_scenario()
    {
        $v1 = $this->service->createDraft(['v' => 1], 'First');
        $this->service->activate($v1, 123);

        $v2 = $this->service->createDraft(['v' => 2], 'Second');
        $this->service->activate($v2, 123);

        $v3 = $this->service->createDraft(['v' => 3], 'Third');
        $this->service->activate($v3, 123);

        // Rollback from v3 - should go to v2
        $this->service->rollback($v3);

        $v2->refresh();
        $v3->refresh();

        $this->assertEquals('active', $v2->status);
        $this->assertEquals('archived', $v3->status);

        // Verify v1 is still archived
        $v1->refresh();
        $this->assertEquals('archived', $v1->status);
    }

    /**
     * Test version_number never decreases
     */
    public function test_version_numbers_always_increase()
    {
        $versions = [];
        for ($i = 0; $i < 10; $i++) {
            $v = $this->service->createDraft(['iteration' => $i], "v$i");
            $versions[] = $v->version_number;
        }

        // Version numbers should be strictly increasing
        for ($i = 1; $i < count($versions); $i++) {
            $this->assertGreaterThan($versions[$i - 1], $versions[$i]);
        }
    }

    /**
     * Test content is preserved through lifecycle
     */
    public function test_content_preserved_through_lifecycle()
    {
        $content = [
            'system_role' => 'helpful assistant',
            'tone' => 'friendly',
            'instructions' => ['be concise', 'be clear'],
        ];

        $version = $this->service->createDraft($content, 'Test');
        $this->service->activate($version, 123);

        $version->refresh();
        $this->assertEquals($content, $version->content);
    }
}
