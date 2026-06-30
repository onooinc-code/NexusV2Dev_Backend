<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\AiModelsHub\UsageTracker;
use App\Models\AIProvider;
use App\Models\AIModel;

class UsageTrackerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_tracks_usage_for_a_provider()
    {
        $provider = AIProvider::factory()->create();
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
            'input_cost_per_m' => 1.50, // $1.50 per million tokens
            'output_cost_per_m' => 2.00, // $2.00 per million tokens
        ]);

        $tracker = new UsageTracker();

        $tracker->trackUsage(
            $provider->id,
            $model->id,
            2000000, // 2 million input tokens
            1000000  // 1 million output tokens
        );

        // Check database
        $this->assertDatabaseHas('usage_logs', [
            'provider_id' => $provider->id,
            'model_id' => $model->id,
            'input_tokens' => 2000000,
            'output_tokens' => 1000000,
            'input_cost' => 3.00, // (2m / 1m) * 1.50 = 3.00
            'output_cost' => 2.00, // (1m / 1m) * 2.00 = 2.00
            'total_cost' => 5.00,
        ]);
    }

    /** @test */
    public function it_gets_usage_stats_for_a_provider()
    {
        $provider = AIProvider::factory()->create();
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
        ]);

        $tracker = new UsageTracker();

        // Track multiple usage events
        $tracker->trackUsage($provider->id, $model->id, 100, 50);
        $tracker->trackUsage($provider->id, $model->id, 50, 25);

        $usage = $tracker->getProviderUsage($provider->id);

        $this->assertCount(2, $usage);
        $this->assertEquals(150, $usage->sum('input_tokens'));
        $this->assertEquals(75, $usage->sum('output_tokens'));
    }

    /** @test */
    public function it_gets_usage_stats_for_a_model()
    {
        $provider = AIProvider::factory()->create();
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
        ]);

        $tracker = new UsageTracker();

        // Track multiple usage events
        $tracker->trackUsage($provider->id, $model->id, 100, 50);
        $tracker->trackUsage($provider->id, $model->id, 50, 25);

        $usage = $tracker->getModelUsage($model->id);

        $this->assertCount(2, $usage);
        $this->assertEquals(150, $usage->sum('input_tokens'));
        $this->assertEquals(75, $usage->sum('output_tokens'));
    }

    /** @test */
    public function it_gets_total_cost_for_a_provider()
    {
        $provider = AIProvider::factory()->create();
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
            'input_cost_per_m' => 1.00,
            'output_cost_per_m' => 2.00,
        ]);

        $tracker = new UsageTracker();

        $tracker->trackUsage($provider->id, $model->id, 1000000, 500000); // 1.00 + 1.00 = 2.00
        $tracker->trackUsage($provider->id, $model->id, 500000, 250000);  // 0.50 + 0.50 = 1.00

        $totalCost = $tracker->getProviderTotalCost($provider->id);

        $this->assertEquals(3.00, $totalCost);
    }

    /** @test */
    public function it_gets_total_cost_for_a_model()
    {
        $provider = AIProvider::factory()->create();
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
            'input_cost_per_m' => 1.00,
            'output_cost_per_m' => 2.00,
        ]);

        $tracker = new UsageTracker();

        $tracker->trackUsage($provider->id, $model->id, 1000000, 500000); // 2.00
        $tracker->trackUsage($provider->id, $model->id, 500000, 250000);  // 1.00

        $totalCost = $tracker->getModelTotalCost($model->id);

        $this->assertEquals(3.00, $totalCost);
    }
}