<?php

namespace App\Http\Controllers\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\SoulyRuntimeProfile;
use App\Models\HedraCloneSource;
use App\Models\HedraProfileFact;
use Illuminate\Http\Request;

class HedraProfileController extends Controller
{
    /**
     * Get Hedra profile with clone sources summary and facts count
     * GET /hedrasoul/profile
     */
    public function show()
    {
        $profile = SoulyRuntimeProfile::first() ?? new SoulyRuntimeProfile();

        $cloneSources = HedraCloneSource::where('is_archived', false)
            ->selectRaw('source_type, COUNT(*) as count')
            ->groupBy('source_type')
            ->get();

        $factsCount = HedraProfileFact::count();

        return response()->json([
            'profile' => $profile,
            'clone_sources_summary' => $cloneSources,
            'facts_count' => $factsCount,
        ]);
    }

    /**
     * Update Hedra profile fields
     * PATCH /hedrasoul/profile
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'autonomy_mode' => 'sometimes|in:chat_only,copilot,operator,autopilot_limited,emergency_paused',
            'memory_access' => 'sometimes|boolean',
            'contact_access' => 'sometimes|boolean',
            'task_execution_access' => 'sometimes|boolean',
            'workflow_execution_access' => 'sometimes|boolean',
            'external_messaging_access' => 'sometimes|boolean',
        ]);

        $profile = SoulyRuntimeProfile::first() ?? new SoulyRuntimeProfile();
        $profile->update($validated);

        return response()->json($profile);
    }
}
