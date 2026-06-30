<?php

namespace App\Http\Controllers;

use App\Services\NexusDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NexusDashboardController extends Controller
{
    public function __construct(private readonly NexusDashboardService $service) {}

    /**
     * GET /api/v1/dashboard/stats
     * Returns aggregated platform metrics from all hubs.
     * Cached for 55s (slightly under the 60s poll interval).
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $data = $this->service->aggregateStats($request->user());
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('Dashboard stats aggregation failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to aggregate dashboard stats.'], 500);
        }
    }

    /**
     * GET /api/v1/dashboard/health
     * Returns live health status for each hub's backing services.
     */
    public function health(Request $request): JsonResponse
    {
        try {
            $data = $this->service->getHealthStatus();
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('Dashboard health probe failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to probe service health.'], 500);
        }
    }

    /**
     * GET /api/v1/dashboard/activity-feed
     * Returns paginated recent cognitive events.
     * Query params: limit (int, default 20, max 100), before (ISO timestamp cursor).
     */
    public function activityFeed(Request $request): JsonResponse
    {
        $request->validate([
            'limit'  => 'nullable|integer|min:1|max:100',
            'before' => 'nullable|string',
        ]);

        try {
            $data = $this->service->getActivityFeed(
                limit: (int) ($request->query('limit', 20)),
                before: $request->query('before'),
            );
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('Dashboard activity feed failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to load activity feed.'], 500);
        }
    }
}
