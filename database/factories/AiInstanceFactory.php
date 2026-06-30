<?php

namespace Database\Factories;

use App\Models\AiInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiInstanceFactory extends Factory
{
    protected $model = AiInstance::class;

    public function definition(): array
    {
        return [
            'name' => 'Test AI Instance',
            'provider' => 'openai',
            'model_name' => 'gpt-4o',
            'is_active' => true,
            'status' => 'active',
            'config' => [],
            'routing_tag' => 'general',
        ];
    }
}
