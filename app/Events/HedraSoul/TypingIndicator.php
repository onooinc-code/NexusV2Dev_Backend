<?php

namespace App\Events\HedraSoul;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingIndicator implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $isTyping;
    public int $userId;

    public function __construct(bool $isTyping, int $userId)
    {
        $this->isTyping = $isTyping;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('hedrasoul.hub.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'typing.indicator';
    }
}
