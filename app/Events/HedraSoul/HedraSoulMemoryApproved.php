<?php

namespace App\Events\HedraSoul;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HedraSoulMemoryApproved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $factId,
        public int $suggestionId,
        public string $memoryType
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('hedrasoul.hub.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'hedrasoul.memory.approved';
    }

    public function broadcastWith(): array
    {
        return [
            'fact_id' => $this->factId,
            'suggestion_id' => $this->suggestionId,
            'memory_type' => $this->memoryType,
        ];
    }
}
