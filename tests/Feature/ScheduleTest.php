<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleTest extends TestCase
{
    public function test_reverb_monitoring_command_is_scheduled(): void
    {
        $schedule = $this->app->make(Schedule::class);

        $commands = collect($schedule->events())
            ->map(fn ($event) => $event->command)
            ->filter()
            ->values()
            ->all();

        $this->assertStringContainsString('monitor:reverb-health', implode(' ', $commands));
    }
}
