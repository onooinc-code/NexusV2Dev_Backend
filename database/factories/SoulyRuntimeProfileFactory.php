<?php

namespace Database\Factories;

use App\Models\SoulyRuntimeProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class SoulyRuntimeProfileFactory extends Factory
{
    protected $model = SoulyRuntimeProfile::class;

    public function definition(): array
    {
        return [
            'autonomy_mode' => 'copilot',
            'is_quarantined' => false,
        ];
    }
}
