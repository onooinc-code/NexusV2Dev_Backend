<?php

namespace App\Http\Controllers\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\HedrasoulApprovalRequest;
use App\Services\HedraSoul\ApprovalInboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HedraSoulApprovalController extends Controller
{
    public function __construct(
        protected ApprovalInboxService $approvalService
    ) {}

    /**
     * List approval requests with optional status filter
     * GET /hedrasoul/approvals
     */
    public function index(Request $request)
    {
        $query = HedrasoulApprovalRequest::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $approvals = $query
            ->orderBy('risk_level', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($approvals);
    }

    /**
     * Get full approval request with context snapshot
     * GET /hedrasoul/approvals/{id}
     */
    public function show(HedrasoulApprovalRequest $approval)
    {
        $context = $approval->contextSnapshot;

        return response()->json([
            'approval' => $approval,
            'context_snapshot' => $context,
        ]);
    }

    /**
     * Approve an action
     * POST /hedrasoul/approvals/{id}/approve
     */
    public function approve(Request $request, HedrasoulApprovalRequest $approval)
    {
        if ($approval->status !== 'pending') {
            return response()->json([
                'error' => 'Can only approve pending requests',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => 'sometimes|string|max:1000',
        ]);

        $this->approvalService->approve(
            $approval,
            Auth::id(),
            $validated['notes'] ?? null
        );

        $approval->refresh();

        return response()->json($approval);
    }

    /**
     * Reject an action
     * POST /hedrasoul/approvals/{id}/reject
     */
    public function reject(Request $request, HedrasoulApprovalRequest $approval)
    {
        if ($approval->status !== 'pending') {
            return response()->json([
                'error' => 'Can only reject pending requests',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => 'sometimes|string|max:1000',
        ]);

        $this->approvalService->reject(
            $approval,
            Auth::id(),
            $validated['notes'] ?? null
        );

        $approval->refresh();

        return response()->json($approval);
    }

    /**
     * Defer an approval decision
     * POST /hedrasoul/approvals/{id}/defer
     */
    public function defer(Request $request, HedrasoulApprovalRequest $approval)
    {
        if ($approval->status !== 'pending') {
            return response()->json([
                'error' => 'Can only defer pending requests',
            ], 422);
        }

        $validated = $request->validate([
            'duration' => 'required|string', // e.g., '1 hour', '30 minutes'
        ]);

        $this->approvalService->defer($approval, $validated['duration']);

        $approval->refresh();

        return response()->json([
            'approval' => $approval,
            'deferred_until' => $approval->deferred_until ?? null,
            'status' => 'deferred',
        ]);
    }
}
