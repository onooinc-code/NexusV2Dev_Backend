<?php

namespace Tests\Feature\HedraSoul;

use Tests\TestCase;
use App\Models\HedrasoulApprovalRequest;
use App\Models\User;
use App\Jobs\HedraSoul\ExecuteSoulyCommandJob;
use App\Jobs\HedraSoul\DispatchApprovalReminderJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class HedraSoulApprovalsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test POST /hedrasoul/approvals/{id}/approve sets status='approved', decided_by, decided_at
     */
    public function test_post_approve_sets_approved_status_and_metadata()
    {
        $approval = HedrasoulApprovalRequest::factory()->create([
            'status' => 'pending',
            'decided_by' => null,
            'decided_at' => null,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/approvals/{$approval->id}/approve", [
                'notes' => 'Approved for execution',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'approved');
        $response->assertJsonPath('decided_by', $this->user->id);

        $this->assertDatabaseHas('hedrasoul_approval_requests', [
            'id' => $approval->id,
            'status' => 'approved',
            'decided_by' => $this->user->id,
        ]);
    }

    /**
     * Test ExecuteSoulyCommandJob is dispatched when approval is approved
     */
    public function test_execute_souly_command_job_is_dispatched_on_approve()
    {
        Queue::fake();

        $approval = HedrasoulApprovalRequest::factory()->create([
            'status' => 'pending',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/approvals/{$approval->id}/approve");

        Queue::assertPushed(ExecuteSoulyCommandJob::class);
    }

    /**
     * Test POST /hedrasoul/approvals/{id}/reject sets status='rejected', no job dispatch
     */
    public function test_post_reject_sets_rejected_status_no_job_dispatch()
    {
        Queue::fake();

        $approval = HedrasoulApprovalRequest::factory()->create([
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/approvals/{$approval->id}/reject", [
                'notes' => 'Not approved for execution',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'rejected');

        $this->assertDatabaseHas('hedrasoul_approval_requests', [
            'id' => $approval->id,
            'status' => 'rejected',
        ]);

        Queue::assertNotPushed(ExecuteSoulyCommandJob::class);
    }

    /**
     * Test POST /hedrasoul/approvals/{id}/defer sets status='deferred'
     */
    public function test_post_defer_sets_deferred_status()
    {
        $approval = HedrasoulApprovalRequest::factory()->create([
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/approvals/{$approval->id}/defer", [
                'duration' => '2h',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'deferred');

        $this->assertDatabaseHas('hedrasoul_approval_requests', [
            'id' => $approval->id,
            'status' => 'deferred',
        ]);
    }

    /**
     * Test DispatchApprovalReminderJob is scheduled when approval is deferred
     */
    public function test_dispatch_approval_reminder_job_is_scheduled_on_defer()
    {
        Queue::fake();

        $approval = HedrasoulApprovalRequest::factory()->create([
            'status' => 'pending',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/hedrasoul/approvals/{$approval->id}/defer", [
                'duration' => '1h',
            ]);

        Queue::assertPushed(DispatchApprovalReminderJob::class);
    }

    /**
     * Test GET /hedrasoul/approvals returns list with optional ?status= filter
     */
    public function test_get_approvals_returns_list_with_status_filter()
    {
        HedrasoulApprovalRequest::factory(3)->create(['status' => 'pending']);
        HedrasoulApprovalRequest::factory(2)->create(['status' => 'approved']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/hedrasoul/approvals?status=pending');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'current_page',
        ]);
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    /**
     * Test GET /hedrasoul/approvals returns list without filter
     */
    public function test_get_approvals_returns_full_list()
    {
        HedrasoulApprovalRequest::factory(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/hedrasoul/approvals');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'current_page',
        ]);
    }

    /**
     * Test GET /hedrasoul/approvals/{id} returns full approval detail
     */
    public function test_get_approval_detail_returns_full_data()
    {
        $approval = HedrasoulApprovalRequest::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/hedrasoul/approvals/{$approval->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('approval.id', $approval->id);
    }

    /**
     * Test POST /hedrasoul/approvals/{id}/approve returns 401 without authentication
     */
    public function test_post_approve_returns_401_without_auth()
    {
        $approval = HedrasoulApprovalRequest::factory()->create();

        $response = $this->postJson("/api/v1/hedrasoul/approvals/{$approval->id}/approve");

        $response->assertStatus(401);
    }

    /**
     * Test POST /hedrasoul/approvals/{id}/reject returns 401 without authentication
     */
    public function test_post_reject_returns_401_without_auth()
    {
        $approval = HedrasoulApprovalRequest::factory()->create();

        $response = $this->postJson("/api/v1/hedrasoul/approvals/{$approval->id}/reject");

        $response->assertStatus(401);
    }

    /**
     * Test POST /hedrasoul/approvals/{id}/defer returns 401 without authentication
     */
    public function test_post_defer_returns_401_without_auth()
    {
        $approval = HedrasoulApprovalRequest::factory()->create();

        $response = $this->postJson("/api/v1/hedrasoul/approvals/{$approval->id}/defer", [
            'duration' => '2h',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test GET /hedrasoul/approvals returns 401 without authentication
     */
    public function test_get_approvals_returns_401_without_auth()
    {
        $response = $this->getJson('/api/v1/hedrasoul/approvals');

        $response->assertStatus(401);
    }

    /**
     * Test GET /hedrasoul/approvals/{id} returns 401 without authentication
     */
    public function test_get_approval_detail_returns_401_without_auth()
    {
        $approval = HedrasoulApprovalRequest::factory()->create();

        $response = $this->getJson("/api/v1/hedrasoul/approvals/{$approval->id}");

        $response->assertStatus(401);
    }
}
