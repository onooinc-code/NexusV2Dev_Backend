<?php

namespace App\Jobs\HedraSoul;

use App\Services\HedraSoul\HedraMemoryMaintenanceService;
use App\Services\HedraSoul\HedraSoulNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * RebuildHedraCloneProfileJob: Rebuilds Hedra clone profile embeddings.
 * 
 * Dispatched by profile maintenance operations or manual user triggers.
 * Calls HedraMemoryMaintenanceService to rebuild embeddings for all hedra_clone_sources.
 */
class RebuildHedraCloneProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300; // 5 minutes for full rebuild

    public function __construct(public ?int $userId = null) {}

    public function handle(): void
    {
        try {
            $maintenanceService = app(HedraMemoryMaintenanceService::class);
            
            // Rebuild embeddings for all clone sources
            // This is a long-running operation that may take several minutes
            $maintenanceService->rebuildEmbeddings();

            // Log completion
            \Log::info('RebuildHedraCloneProfileJob completed', [
                'user_id' => $this->userId,
            ]);

        } catch (Throwable $e) {
            $this->failed($e);
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $e): void
    {
        \Log::error('RebuildHedraCloneProfileJob failed', [
            'user_id' => $this->userId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Create failure notification if user ID is provided
        if ($this->userId) {
            try {
                $notificationService = app(HedraSoulNotificationService::class);
                $notificationService->create(
                    type: 'maintenance_failure',
                    priority: 'medium',
                    title: 'Profile Rebuild Failed',
                    body: 'Failed to rebuild Hedra clone profile embeddings. Your profile will be rebuilt automatically on next access.',
                    relatedId: $this->userId,
                    relatedType: 'user',
                );
            } catch (\Exception $notificationError) {
                \Log::warning('Failed to create maintenance failure notification', [
                    'user_id' => $this->userId,
                    'error' => $notificationError->getMessage(),
                ]);
            }
        }
    }
}
