<?php

namespace Database\Factories;

use App\Models\HedrasoulApprovalRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class HedrasoulApprovalRequestFactory extends Factory
{
    protected $model = HedrasoulApprovalRequest::class;

    public function definition(): array
    {
        return [
            'source_type' => 'task',
            'action_description' => $this->faker->sentence(),
            'inputs' => ['key' => 'value'],
            'risk_level' => 'low',
            'status' => 'pending',
        ];
    }
}
