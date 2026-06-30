<?php

namespace Database\Factories;

use App\Models\SoulyInstructionVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class SoulyInstructionVersionFactory extends Factory
{
    protected $model = SoulyInstructionVersion::class;

    public function definition(): array
    {
        return [
            'version_number' => $this->faker->unique()->numberBetween(1, 1000),
            'status' => 'draft',
            'content' => ['instructions' => 'Do something'],
        ];
    }
}
