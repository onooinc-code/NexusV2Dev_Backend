<?php

namespace App\Services\HedraSoul;

use App\Models\SoulyRuntimeProfile;

/**
 * SoulyRuntimeProfileService: Manages Souly's runtime configuration and state.
 * Maintains autonomy mode, active model, instruction version, and quarantine status.
 */
class SoulyRuntimeProfileService
{
    /**
     * Get the current runtime profile (or create one if none exists).
     */
    public function getCurrent(): SoulyRuntimeProfile
    {
        $profile = SoulyRuntimeProfile::orderBy('id', 'desc')->first();

        if (!$profile) {
            $profile = SoulyRuntimeProfile::create([
                'autonomy_mode' => 'copilot',
                'tool_permissions' => ['search', 'draft', 'read_memory'],
                'memory_access' => true,
                'contact_access' => true,
                'task_execution_access' => true,
                'workflow_execution_access' => false,
                'external_messaging_access' => false,
                'is_quarantined' => false,
            ]);
        }

        return $profile;
    }

    /**
     * Update runtime profile fields.
     */
    public function update(array $data): SoulyRuntimeProfile
    {
        $profile = $this->getCurrent();
        $profile->update($data);
        return $profile;
    }

    /**
     * Update autonomy mode and broadcast change.
     */
    public function updateAutonomyMode(string $mode): void
    {
        $profile = $this->getCurrent();
        $profile->update(['autonomy_mode' => $mode]);

        // Broadcast change
        app(HedraSoulRealtimeBroadcaster::class)->broadcastAutonomyChanged([
            'autonomy_mode' => $mode,
            'changed_by' => auth()->id(),
            'changed_at' => now()->toIso8601String(),
        ], auth()->id());
    }

    /**
     * Update active model instance.
     */
    public function updateActiveModel(int $modelInstanceId): void
    {
        $profile = $this->getCurrent();
        
        // Validate model exists in AiModelsHub (basic check)
        // $model = \App\Models\AiInstance::find($modelInstanceId);
        // if (!$model) throw new \Exception('Model not found');

        $profile->update(['active_model_instance_id' => $modelInstanceId]);

        // Broadcast change
        app(HedraSoulRealtimeBroadcaster::class)->broadcastModelChanged([
            'model_instance_id' => $modelInstanceId,
            'changed_at' => now()->toIso8601String(),
        ], auth()->id());
    }

    /**
     * Update active instruction version.
     */
    public function updateActiveInstructionVersion(int $instructionVersionId): void
    {
        $profile = $this->getCurrent();
        $profile->update(['active_instruction_version_id' => $instructionVersionId]);
    }

    /**
     * Quarantine Souly (emergency pause all actions).
     */
    public function setQuarantine(): void
    {
        $profile = $this->getCurrent();
        $profile->update([
            'is_quarantined' => true,
            'autonomy_mode' => 'emergency_paused',
        ]);

        // Broadcast quarantine event
        app(HedraSoulRealtimeBroadcaster::class)->broadcastAutonomyChanged([
            'autonomy_mode' => 'emergency_paused',
            'quarantine_reason' => 'Manual quarantine triggered',
            'changed_by' => auth()->id(),
            'changed_at' => now()->toIso8601String(),
        ], auth()->id());
    }

    /**
     * Resume from quarantine (restore previous autonomy mode).
     */
    public function resume(): void
    {
        $profile = $this->getCurrent();
        $profile->update([
            'is_quarantined' => false,
            'autonomy_mode' => 'copilot',  // Default to copilot on resume
        ]);

        // Broadcast resume event
        app(HedraSoulRealtimeBroadcaster::class)->broadcastAutonomyChanged([
            'autonomy_mode' => 'copilot',
            'changed_by' => auth()->id(),
            'changed_at' => now()->toIso8601String(),
        ], auth()->id());
    }

    /**
     * Update tool permissions.
     */
    public function updateToolPermissions(array $permissions): void
    {
        $profile = $this->getCurrent();
        $profile->update(['tool_permissions' => $permissions]);
    }

    /**
     * Update access controls (memory, contact, task, workflow, external).
     */
    public function updateAccessControls(array $data): void
    {
        $profile = $this->getCurrent();
        $allowed_fields = [
            'memory_access',
            'contact_access',
            'task_execution_access',
            'workflow_execution_access',
            'external_messaging_access',
        ];

        $update_data = array_intersect_key($data, array_flip($allowed_fields));
        $profile->update($update_data);
    }

    /**
     * Check if Souly is currently quarantined.
     */
    public function isQuarantined(): bool
    {
        return $this->getCurrent()->is_quarantined;
    }

    /**
     * Get current autonomy mode.
     */
    public function getAutonomyMode(): string
    {
        return $this->getCurrent()->autonomy_mode;
    }
}
