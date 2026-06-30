<?php

namespace App\Jobs\HedraSoul;

use App\Models\HedrasoulSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateSessionSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public HedrasoulSession $session) {}

    public function handle(): void
    {
        $this->session->update(['summary' => 'Generated summary of session activities.']);
    }
}
