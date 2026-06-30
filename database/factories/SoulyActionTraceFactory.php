<?php

namespace Database\Factories;

use App\Models\SoulyActionTrace;
use Illuminate\Database\Eloquent\Factories\Factory;

class SoulyActionTraceFactory extends Factory
{
    protected $model = SoulyActionTrace::class;

    public function definition(): array
    {
        return [
            'trace_id' => $this->faker->unique()->uuid(),
            'parsed_intent' => 'test_intent',
            'selected_action' => 'test_action',
            'final_output' => 'Test output',
        ];
    }
}
