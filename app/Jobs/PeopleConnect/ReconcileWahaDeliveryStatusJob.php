<?php

namespace App\Jobs\PeopleConnect;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PeopleConnect\PeopleConnectDeliveryAttempt;
use Throwable;

class ReconcileWahaDeliveryStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Simple stub for reconciling delivery status
        // Usually you'd fetch from WAHA and update attempts and messages
    }
    
    public function failed(Throwable $exception): void
    {
        \Log::error('ReconcileWahaDeliveryStatusJob failed: ' . $exception->getMessage());
    }
}
