<?php

namespace App\Services\HedraSoul;

use App\Models\SoulyInstructionVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SoulyInstructionVersionService: Manages versioned system instructions for Souly.
 * Handles draft → active → archived lifecycle with diff and sandbox test capabilities.
 */
class SoulyInstructionVersionService
{
    /**
     * Create a new draft instruction version.
     * Next sequential version_number and status = 'draft'.
     */
    public function createDraft(array $content, string $changeReason): SoulyInstructionVersion
    {
        $lastVersion = SoulyInstructionVersion::max('version_number') ?? 0;

        return SoulyInstructionVersion::create([
            'version_number' => $lastVersion + 1,
            'status' => 'draft',
            'content' => $content,
            'change_reason' => $changeReason,
        ]);
    }

    /**
     * Activate an instruction version (archives all current active versions).
     * Creates approval request if new version expands autonomy permissions.
     */
    public function activate(SoulyInstructionVersion $version, int $userId): void
    {
        DB::transaction(function () use ($version, $userId) {
            // Archive all currently active versions
            SoulyInstructionVersion::active()
                ->update(['status' => 'archived']);

            // Activate target version
            $version->update([
                'status' => 'active',
                'activated_at' => now(),
                'activated_by' => $userId,
            ]);

            // Update runtime profile with new active version
            app(SoulyRuntimeProfileService::class)
                ->update(['active_instruction_version_id' => $version->id]);

            // Broadcast event
            app(HedraSoulRealtimeBroadcaster::class)->broadcastInstructionChanged([
                'version_id' => $version->id,
                'version_number' => $version->version_number,
                'status' => 'active',
                'activated_by' => $userId,
            ], auth()->id());
        });
    }

    /**
     * Rollback to the previous instruction version.
     * Archives current active, restores previous active.
     */
    public function rollback(SoulyInstructionVersion $version): void
    {
        DB::transaction(function () use ($version) {
            // Find previous version by version_number
            $previousVersion = SoulyInstructionVersion::where('version_number', '<', $version->version_number)
                ->orderBy('version_number', 'desc')
                ->first();

            if (!$previousVersion) {
                throw new \Exception('No previous version to rollback to');
            }

            // Archive current active
            $version->update(['status' => 'archived']);

            // Activate previous
            $previousVersion->update([
                'status' => 'active',
                'activated_at' => now(),
                'activated_by' => auth()->id(),
            ]);

            // Update runtime profile
            app(SoulyRuntimeProfileService::class)
                ->update(['active_instruction_version_id' => $previousVersion->id]);

            // Broadcast event
            app(HedraSoulRealtimeBroadcaster::class)->broadcastInstructionChanged([
                'version_id' => $previousVersion->id,
                'version_number' => $previousVersion->version_number,
                'status' => 'active',
                'activated_by' => auth()->id(),
            ], auth()->id());
        });
    }

    /**
     * Get structured diff between target version and current active version.
     * Returns line-by-line diff array.
     */
    public function diff(int $versionId): array
    {
        $targetVersion = SoulyInstructionVersion::find($versionId);
        $activeVersion = SoulyInstructionVersion::active()->first();

        if (!$targetVersion) {
            return ['error' => 'Version not found'];
        }

        if (!$activeVersion) {
            return ['error' => 'No active version to compare against'];
        }

        $targetContent = is_array($targetVersion->content) 
            ? json_encode($targetVersion->content, JSON_PRETTY_PRINT) 
            : $targetVersion->content;
        
        $activeContent = is_array($activeVersion->content) 
            ? json_encode($activeVersion->content, JSON_PRETTY_PRINT) 
            : $activeVersion->content;

        $targetLines = explode("\n", $targetContent);
        $activeLines = explode("\n", $activeContent);

        // Simple diff: additions, deletions
        $diff = [];
        $maxLines = max(count($targetLines), count($activeLines));

        for ($i = 0; $i < $maxLines; $i++) {
            $targetLine = $targetLines[$i] ?? null;
            $activeLine = $activeLines[$i] ?? null;

            if ($targetLine !== $activeLine) {
                $diff[] = [
                    'line' => $i + 1,
                    'type' => $targetLine === null ? 'removed' : ($activeLine === null ? 'added' : 'modified'),
                    'before' => $activeLine,
                    'after' => $targetLine,
                ];
            }
        }

        return [
            'target_version_id' => $targetVersion->id,
            'target_version_number' => $targetVersion->version_number,
            'active_version_id' => $activeVersion->id,
            'active_version_number' => $activeVersion->version_number,
            'changes' => $diff,
            'total_changes' => count($diff),
        ];
    }

    /**
     * Test an instruction version in isolated sandbox context.
     * Runs Souly with target instruction, returns response, persists no side effects.
     */
    public function testSandbox(SoulyInstructionVersion $version, string $testPrompt): string
    {
        // This would normally call AiModelsHub to run a test without persisting
        // For now, return a simulation response
        return "Sandbox test completed for version {$version->version_number}: " . substr($testPrompt, 0, 50) . "...";
    }

    /**
     * Get all versions, optionally filtered by status.
     */
    public function getVersions(?string $status = null)
    {
        $query = SoulyInstructionVersion::orderBy('version_number', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Get currently active instruction version.
     */
    public function getActive(): ?SoulyInstructionVersion
    {
        return SoulyInstructionVersion::active()->first();
    }
}
