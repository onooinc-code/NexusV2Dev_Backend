<?php

namespace Database\Factories;

use App\Models\HedraMemorySuggestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class HedraMemorySuggestionFactory extends Factory
{
    protected $model = HedraMemorySuggestion::class;

    public function definition(): array
    {
        return [
            'content' => $this->faker->sentence(),
            'memory_type' => 'working',
            'confidence' => 0.9,
            'status' => 'pending',
        ];
    }
}
