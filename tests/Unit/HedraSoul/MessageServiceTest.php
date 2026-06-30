<?php

namespace Tests\Unit\HedraSoul;

use Tests\TestCase;
use App\Models\HedrasoulSession;
use App\Models\HedrasoulMessage;
use App\Models\HedrasoulContextSnapshot;
use App\Services\HedraSoul\HedraSoulMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MessageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_a_message()
    {
        $session = HedrasoulSession::factory()->create([
            'title' => 'Test Session',
            'status' => 'active',
            'last_autonomy_mode' => 'copilot',
        ]);

        $snapshot = HedrasoulContextSnapshot::create([
            'session_id' => $session->id,
            'payload' => [],
        ]);

        $service = app(HedraSoulMessageService::class);
        
        $message = $service->storeMessage($session, 'Hello Hedra', 'user', []);
        
        $this->assertInstanceOf(HedrasoulMessage::class, $message);
        $this->assertEquals('Hello Hedra', $message->body);
        $this->assertEquals('user', $message->sender_type);
        $this->assertDatabaseHas('hedrasoul_messages', [
            'id' => $message->id,
            'body' => 'Hello Hedra'
        ]);
    }
}

