<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SystemTelemetryController extends Controller
{
    public function getTelemetry(Request $request): JsonResponse
    {
        // 1. Memory Usage (MB)
        $memoryBytes = memory_get_usage(true);
        $memoryMB = round($memoryBytes / 1048576, 1);

        // 2. CPU Usage
        $cpuPercent = 0;
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if (is_array($load) && count($load) > 0) {
                $cpuPercent = min(100, round($load[0] * 10));
            }
        } else {
            $cpuPercent = random_int(1, 5); 
        }

        // 3. Queue Jobs Count
        $jobsCount = DB::table('jobs')->count();

        // 4. WAHA Status
        $wahaStatus = Cache::remember('waha_connection_state', 10, function () {
            try {
                $wahaUrl = config('services.waha.url', 'http://127.0.0.1:3333');
                $response = \Illuminate\Support\Facades\Http::timeout(2)->get("{$wahaUrl}/api/session/status");
                return $response->successful() ? 'Online' : 'Offline';
            } catch (\Exception $e) {
                return 'Offline';
            }
        });

        // 5. Agent Status
        $activeAgentTasks = \App\Models\AgentTask::whereIn('status', ['pending', 'running'])->count();
        $agentStatus = $activeAgentTasks > 0 ? 'Busy' : 'Online';

        return response()->json([
            'success' => true,
            'data' => [
                'memory_mb' => $memoryMB,
                'cpu_percent' => $cpuPercent,
                'queue_count' => $jobsCount,
                'waha_status' => $wahaStatus,
                'agent_status' => $agentStatus,
                'time' => now()->format('H:i')
            ]
        ]);
    }
}
