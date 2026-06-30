<?php

namespace App\Jobs\HedraSoul;

use App\Models\HedraCloneSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IngestHedraCloneSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public HedraCloneSource $source) {}

    public function handle(): void
    {
        $this->source->update(['validation_status' => 'processed']);
    }
}
