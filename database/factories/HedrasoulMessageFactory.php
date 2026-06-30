<?php

namespace Database\Factories;

use App\Models\HedrasoulMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class HedrasoulMessageFactory extends Factory
{
    protected $model = HedrasoulMessage::class;

    public function definition(): array
    {
        return [
            'body' => 'Test message',
            'session_id' => 1,
            'sender_type' => 'user',
        ];
    }
}
