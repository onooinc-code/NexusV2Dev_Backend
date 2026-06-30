<?php

namespace Database\Factories;

use App\Models\HedraProfileFact;
use Illuminate\Database\Eloquent\Factories\Factory;

class HedraProfileFactFactory extends Factory
{
    protected $model = HedraProfileFact::class;

    public function definition(): array
    {
        return [
            'memory_type' => 'working',
            'content' => $this->faker->sentence(),
            'confidence' => 0.9,
            'sensitivity' => 'private',
            'visibility_scope' => 'private',
            'is_approved' => false,
            'version' => 1,
        ];
    }
}
