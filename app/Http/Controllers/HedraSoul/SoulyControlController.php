<?php

namespace App\Http\Controllers\HedraSoul;

use App\Http\Controllers\Controller;
use App\Services\HedraSoul\SoulyRuntimeProfileService;
use App\Services\HedraSoul\SoulyCommandRouter;
use App\Services\HedraSoul\SoulyActionPolicyService;
use App\Models\HedrasoulMessage;
use Illuminate\Http\Request;

class SoulyControlController extends Controller
{
    public function __construct(
        protected SoulyRuntimeProfileService $profileService,
        protected SoulyCommandRouter $commandRouter,
        protected SoulyActionPolicyService $policyService
    ) {}

    /**
     * Get current Souly runtime profile status
     * GET /hedrasoul/souly/status
     */
    public function status()
    {
        $profile = $this->profileService->getCurrent();

        return response()->json($profile);
    }

    /**
     * Update autonomy mode
     * PATCH /hedrasoul/souly/autonomy
     */
    public function updateAutonomy(Request $request)
    {
        $validated = $request->validate([
            'autonomy_mode' => 'required|in:chat_only,copilot,operator,autopilot_limited,emergency_paused',
        ]);

        $this->profileService->updateAutonomyMode($validated['autonomy_mode']);

        $profile = $this->profileService->getCurrent();

        return response()->json($profile);
    }

    /**
     * Change active model instance
     * PATCH /hedrasoul/souly/model
     */
    public function updateModel(Request $request)
    {
        $validated = $request->validate([
            'model_instance_id' => 'required|integer|exists:ai_instances,id',
        ]);

        $this->profileService->updateActiveModel($validated['model_instance_id']);

        $profile = $this->profileService->getCurrent();

        return response()->json($profile);
    }

    /**
     * Quarantine Souly (block all commands)
     * POST /hedrasoul/souly/quarantine
     */
    public function quarantine()
    {
        $this->profileService->setQuarantine();

        $profile = $this->profileService->getCurrent();

        return response()->json([
            'status' => 'quarantined',
            'profile' => $profile,
        ]);
    }

    /**
     * Resume from quarantine
     * POST /hedrasoul/souly/resume
     */
    public function resume()
    {
        $this->profileService->resume();

        $profile = $this->profileService->getCurrent();

        return response()->json([
            'status' => 'resumed',
            'profile' => $profile,
        ]);
    }

    /**
     * Simulate a command without executing (dry-run)
     * POST /hedrasoul/souly/simulate
     */
    public function simulate(Request $request)
    {
        $validated = $request->validate([
            'body' => 'required|string',
            'session_id' => 'required|integer|exists:hedrasoul_sessions,id',
        ]);

        // Create a temporary message for classification
        $tempMessage = new HedrasoulMessage([
            'body' => $validated['body'],
            'session_id' => $validated['session_id'],
        ]);

        $commandIntent = $this->commandRouter->classify($tempMessage);
        $policyResult = $this->policyService->canExecute(
            $commandIntent->intent,
            $commandIntent->riskLevel
        );

        return response()->json([
            'intent' => $commandIntent->intent,
            'risk_level' => $commandIntent->riskLevel,
            'policy_result' => [
                'allowed' => $policyResult->allowed,
                'explanation' => $policyResult->explanation ?? null,
            ],
            'would_execute' => $policyResult->allowed,
        ]);
    }
}
