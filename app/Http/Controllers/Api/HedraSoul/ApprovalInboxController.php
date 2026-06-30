<?php

namespace App\Http\Controllers\Api\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\HedrasoulApprovalRequest;
use App\Services\HedraSoul\ApprovalInboxService;
use Illuminate\Http\Request;

class ApprovalInboxController extends Controller
{
    public function index()
    {
        return response()->json(['data' => HedrasoulApprovalRequest::pending()->orderBy('created_at', 'desc')->get()]);
    }

    public function approve(Request $request, HedrasoulApprovalRequest $approval, ApprovalInboxService $service)
    {
        $notes = $request->input('notes');
        $service->approve($approval, 1, $notes);
        return response()->json(['data' => $approval->fresh()]);
    }

    public function reject(Request $request, HedrasoulApprovalRequest $approval, ApprovalInboxService $service)
    {
        $notes = $request->input('notes');
        $service->reject($approval, 1, $notes);
        return response()->json(['data' => $approval->fresh()]);
    }

    public function defer(Request $request, HedrasoulApprovalRequest $approval, ApprovalInboxService $service)
    {
        $duration = $request->input('duration', '1h');
        $service->defer($approval, $duration);
        return response()->json(['data' => $approval->fresh()]);
    }
}
