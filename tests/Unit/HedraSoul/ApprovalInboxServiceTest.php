<?php

namespace Tests\Unit\HedraSoul;

use Tests\TestCase;
use App\Models\HedrasoulApprovalRequest;
use App\Services\HedraSoul\ApprovalInboxService;
use App\Jobs\HedraSoul\ExecuteSoulyCommandJob;
use App\Jobs\HedraSoul\DispatchApprovalReminderJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ApprovalInboxServiceTest: Tests approval request lifecycle.
 * Validates: Requirements 8, Correctness Properties 1-12
 */
class ApprovalInboxServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalInboxService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed the users referenced as decided_by in approve/reject tests
        \DB::table('users')->insertOrIgnore([
            'id'                => 123,
            'name'              => 'Test Approver',
            'email'             => 'approver@test.com',
            'password'          => bcrypt('password'),
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
        \DB::table('users')->insertOrIgnore([
            'id'                => 456,
            'name'              => 'Test Rejecter',
            'email'             => 'rejecter@test.com',
            'password'          => bcrypt('password'),
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
        $this->service = app(ApprovalInboxService::class);
        Queue::fake();
    }

    /**
     * Test create() produces record with status=pending
     */
    public function test_create_produces_pending_request()
    {
        $data = [
            'source_type' => 'task',
            'source_id' => 1,
            'action_description' => 'Create a new task',
            'inputs' => ['name' => 'Test Task'],
            'risk_level' => 'write_low',
        ];

        $request = $this->service->create($data);

        $this->assertInstanceOf(HedrasoulApprovalRequest::class, $request);
        $this->assertEquals('pending', $request->status);
        $this->assertDatabaseHas('hedrasoul_approval_requests', [
            'id' => $request->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Test approve() sets status=approved, sets decided_by and decided_at
     */
    public function test_approve_sets_approved_status()
    {
        $request = HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'source_id' => 1,
            'action_description' => 'Create task',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'pending',
        ]);

        $userId = 123;
        $this->service->approve($request, $userId, 'Looks good');

        $this->assertDatabaseHas('hedrasoul_approval_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'decided_by' => $userId,
        ]);

        // Refresh from DB to verify
        $request->refresh();
        $this->assertEquals('approved', $request->status);
        $this->assertEquals($userId, $request->decided_by);
        $this->assertNotNull($request->decided_at);
    }

    /**
     * Test approve() dispatches ExecuteSoulyCommandJob
     */
    public function test_approve_dispatches_job()
    {
        Queue::fake();

        $request = HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'source_id' => 1,
            'action_description' => 'Create task',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'pending',
        ]);

        $this->service->approve($request, 123);

        Queue::assertPushed(ExecuteSoulyCommandJob::class);
    }

    /**
     * Test reject() sets status=rejected
     */
    public function test_reject_sets_rejected_status()
    {
        $request = HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'source_id' => 1,
            'action_description' => 'Create task',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'pending',
        ]);

        $userId = 123;
        $this->service->reject($request, $userId, 'Not approved');

        $this->assertDatabaseHas('hedrasoul_approval_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'decided_by' => $userId,
        ]);
    }

    /**
     * Test reject() does NOT dispatch ExecuteSoulyCommandJob
     */
    public function test_reject_does_not_dispatch_job()
    {
        Queue::fake();

        $request = HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'source_id' => 1,
            'action_description' => 'Create task',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'pending',
        ]);

        $this->service->reject($request, 123);

        Queue::assertNotPushed(ExecuteSoulyCommandJob::class);
    }

    /**
     * Test defer() sets status=deferred
     */
    public function test_defer_sets_deferred_status()
    {
        Queue::fake();

        $request = HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'source_id' => 1,
            'action_description' => 'Create task',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'pending',
        ]);

        $this->service->defer($request, '2h');

        $this->assertDatabaseHas('hedrasoul_approval_requests', [
            'id' => $request->id,
            'status' => 'deferred',
        ]);
    }

    /**
     * Test defer() schedules DispatchApprovalReminderJob
     */
    public function test_defer_schedules_reminder_job()
    {
        Queue::fake();

        $request = HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'source_id' => 1,
            'action_description' => 'Create task',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'pending',
        ]);

        $this->service->defer($request, '2h');

        Queue::assertPushed(DispatchApprovalReminderJob::class);
    }

    /**
     * Test getPendingApprovals() returns only pending requests
     */
    public function test_get_pending_approvals()
    {
        // Create mix of pending and approved requests
        HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'action_description' => 'Task 1',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'pending',
        ]);

        HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'action_description' => 'Task 2',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'approved',
        ]);

        HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'action_description' => 'Task 3',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'pending',
        ]);

        $pending = $this->service->getPendingApprovals();

        $this->assertCount(2, $pending->items());
    }

    /**
     * Test getApprovals() with status filter
     */
    public function test_get_approvals_with_status_filter()
    {
        HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'action_description' => 'Task 1',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'pending',
        ]);

        HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'action_description' => 'Task 2',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'approved',
        ]);

        $approved = $this->service->getApprovals('approved');

        $this->assertCount(1, $approved->items());
        $this->assertEquals('approved', $approved->items()[0]['status']);
    }

    /**
     * Test getApproval() by id
     */
    public function test_get_approval_by_id()
    {
        $request = HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'action_description' => 'Task 1',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'pending',
        ]);

        $retrieved = $this->service->getApproval($request->id);

        $this->assertInstanceOf(HedrasoulApprovalRequest::class, $retrieved);
        $this->assertEquals($request->id, $retrieved->id);
    }

    /**
     * Test approve() preserves decision_notes
     */
    public function test_approve_preserves_decision_notes()
    {
        $request = HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'action_description' => 'Create task',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'pending',
        ]);

        $notes = 'This looks good to proceed';
        $this->service->approve($request, 123, $notes);

        $this->assertDatabaseHas('hedrasoul_approval_requests', [
            'id' => $request->id,
            'decision_notes' => $notes,
        ]);
    }

    /**
     * Test reject() preserves decision_notes
     */
    public function test_reject_preserves_decision_notes()
    {
        $request = HedrasoulApprovalRequest::create([
            'source_type' => 'task',
            'action_description' => 'Create task',
            'inputs' => [],
            'risk_level' => 'write_low',
            'status' => 'pending',
        ]);

        $notes = 'Not the right time for this';
        $this->service->reject($request, 123, $notes);

        $this->assertDatabaseHas('hedrasoul_approval_requests', [
            'id' => $request->id,
            'decision_notes' => $notes,
        ]);
    }

    /**
     * Test multiple approval cycles
     */
    public function test_multiple_approval_cycles()
    {
        Queue::fake();

        // Create request
        $request = $this->service->create([
            'source_type' => 'task',
            'action_description' => 'Create task',
            'inputs' => [],
            'risk_level' => 'write_low',
        ]);

        $this->assertEquals('pending', $request->status);

        // Approve it
        $this->service->approve($request, 123);
        $request->refresh();
        $this->assertEquals('approved', $request->status);

        // Create another
        $request2 = $this->service->create([
            'source_type' => 'task',
            'action_description' => 'Another task',
            'inputs' => [],
            'risk_level' => 'write_medium',
        ]);

        $this->assertEquals('pending', $request2->status);

        // Reject it
        $this->service->reject($request2, 456);
        $request2->refresh();
        $this->assertEquals('rejected', $request2->status);
    }
}
