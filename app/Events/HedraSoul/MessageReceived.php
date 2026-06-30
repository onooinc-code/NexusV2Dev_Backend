<?php

namespace App\Events\HedraSoul;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $payload;
    public int $userId;

    public function __construct(array $payload, int $userId)
    {
        $this->payload = $payload;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('hedrasoul.hub.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'message.received';
    }
}
