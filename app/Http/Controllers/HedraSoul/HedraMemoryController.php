<?php

namespace App\Http\Controllers\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\HedraProfileFact;
use App\Models\HedraMemorySuggestion;
use App\Services\HedraSoul\HedraMemoryService;
use App\Services\HedraSoul\HedraMemoryMaintenanceService;
use App\Jobs\HedraSoul\RebuildHedraCloneProfileJob;
use Illuminate\Http\Request;

class HedraMemoryController extends Controller
{
    public function __construct(
        protected HedraMemoryService $memoryService,
        protected HedraMemoryMaintenanceService $maintenanceService
    ) {}

    /**
     * List profile facts and suggestions with filters
     * GET /hedrasoul/memories
     */
    public function index(Request $request)
    {
        $factsQuery = HedraProfileFact::query();
        $suggestionsQuery = HedraMemorySuggestion::query();

        if ($request->has('type')) {
            $factsQuery->where('memory_type', $request->type);
            $suggestionsQuery->where('memory_type', $request->type);
        }

        if ($request->has('status')) {
            $suggestionsQuery->where('status', $request->status);
        }

        $facts = $factsQuery->orderBy('created_at', 'desc')->paginate($request->per_page ?? 50);
        $suggestions = $suggestionsQuery->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'facts' => $facts,
            'pending_suggestions' => $suggestions,
        ]);
    }

    /**
     * Create a new memory fact
     * POST /hedrasoul/memories
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'memory_type' => 'required|in:working,episodic,semantic,structured,graph,preference,tone_style,decision,boundary,correction',
            'content' => 'required|string',
            'confidence' => 'required|numeric|min:0|max:1',
            'sensitivity' => 'required|in:public,internal,confidential,restricted',
            'visibility_scope' => 'required|in:personal,team,organization',
            'evidence' => 'sometimes|array',
        ]);

        $fact = $this->memoryService->createFact($validated);

        return response()->json($fact, 201);
    }

    /**
     * Update a memory fact
     * PATCH /hedrasoul/memories/{id}
     */
    public function update(Request $request, HedraProfileFact $memory)
    {
        $validated = $request->validate([
            'content' => 'sometimes|string',
            'confidence' => 'sometimes|numeric|min:0|max:1',
            'sensitivity' => 'sometimes|in:public,internal,confidential,restricted',
            'visibility_scope' => 'sometimes|in:personal,team,organization',
            'evidence' => 'sometimes|array',
        ]);

        $updated = $this->memoryService->updateFact($memory, $validated);

        return response()->json($updated);
    }

    /**
     * Approve a memory suggestion
     * POST /hedrasoul/memories/{id}/approve
     */
    public function approve(HedraMemorySuggestion $memory)
    {
        $fact = $this->memoryService->approve($memory);

        return response()->json([
            'suggestion_id' => $memory->id,
            'fact_id' => $fact->id,
            'status' => 'approved',
        ]);
    }

    /**
     * Reject a memory suggestion
     * POST /hedrasoul/memories/{id}/reject
     */
    public function reject(HedraMemorySuggestion $memory)
    {
        $this->memoryService->reject($memory);

        return response()->json([
            'suggestion_id' => $memory->id,
            'status' => 'rejected',
        ]);
    }

    /**
     * Run memory maintenance
     * POST /hedrasoul/memory-maintenance
     */
    public function maintenance(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:rebuild_embeddings,prune_stale',
        ]);

        if ($validated['action'] === 'rebuild_embeddings') {
            dispatch(new RebuildHedraCloneProfileJob());
        } elseif ($validated['action'] === 'prune_stale') {
            $pruned = $this->maintenanceService->pruneStale();
            return response()->json([
                'action' => 'prune_stale',
                'records_pruned' => $pruned,
            ], 202);
        }

        return response()->json([
            'action' => $validated['action'],
            'status' => 'dispatched',
        ], 202);
    }
}
