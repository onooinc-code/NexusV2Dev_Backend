<?php

namespace Database\Factories;

use App\Models\HedrasoulContextSnapshot;
use App\Models\HedrasoulSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class HedrasoulContextSnapshotFactory extends Factory
{
    protected $model = HedrasoulContextSnapshot::class;

    public function definition(): array
    {
        return [
            'session_id' => HedrasoulSession::factory(),
            'payload' => [],
            'token_estimate' => 0,
            'created_at' => now(),
        ];
    }
}
