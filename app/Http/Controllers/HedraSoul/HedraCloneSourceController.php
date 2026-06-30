<?php

namespace App\Http\Controllers\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\HedraCloneSource;
use App\Services\HedraSoul\HedraCloneProfileService;
use Illuminate\Http\Request;

class HedraCloneSourceController extends Controller
{
    public function __construct(
        protected HedraCloneProfileService $cloneService
    ) {}

    /**
     * List non-archived clone sources grouped by source_type
     * GET /hedrasoul/clone-sources
     */
    public function index(Request $request)
    {
        $sources = HedraCloneSource::where('is_archived', false)
            ->orderBy('source_type')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        // Group by source_type
        $grouped = $sources->groupBy('source_type');

        return response()->json([
            'data' => $sources->items(),
            'grouped_summary' => $grouped->map(fn($items) => [
                'source_type' => $items->first()->source_type,
                'count' => $items->count(),
            ])->values(),
            'pagination' => [
                'total' => $sources->total(),
                'per_page' => $sources->perPage(),
                'current_page' => $sources->currentPage(),
                'last_page' => $sources->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new clone source
     * POST /hedrasoul/clone-sources
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_type' => 'required|string|max:100',
            'content' => 'required|string',
            'sensitivity' => 'required|in:public,internal,confidential,restricted',
            'visibility_scope' => 'required|in:personal,team,organization',
            'confidence' => 'sometimes|numeric|min:0|max:1',
            'validation_status' => 'sometimes|in:pending,validated,rejected',
            'provenance' => 'sometimes|string|max:500',
        ]);

        $source = $this->cloneService->create($validated);

        return response()->json($source, 201);
    }

    /**
     * Update a clone source
     * PATCH /hedrasoul/clone-sources/{id}
     */
    public function update(Request $request, HedraCloneSource $source)
    {
        $validated = $request->validate([
            'content' => 'sometimes|string',
            'sensitivity' => 'sometimes|in:public,internal,confidential,restricted',
            'visibility_scope' => 'sometimes|in:personal,team,organization',
            'confidence' => 'sometimes|numeric|min:0|max:1',
            'validation_status' => 'sometimes|in:pending,validated,rejected',
            'provenance' => 'sometimes|string|max:500',
        ]);

        $updated = $this->cloneService->update($source, $validated);

        return response()->json($updated);
    }

    /**
     * Delete a clone source (hard delete)
     * DELETE /hedrasoul/clone-sources/{id}
     */
    public function destroy(HedraCloneSource $source)
    {
        $this->cloneService->delete($source);

        return response()->json(null, 204);
    }
}
