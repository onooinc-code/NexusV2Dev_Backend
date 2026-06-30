<?php

namespace App\Events\HedraSoul;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HedraSoulMemorySuggested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $suggestionId,
        public string $memoryType,
        public string $contentPreview
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('hedrasoul.hub.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'hedrasoul.memory.suggested';
    }

    public function broadcastWith(): array
    {
        return [
            'suggestion_id' => $this->suggestionId,
            'memory_type' => $this->memoryType,
            'content_preview' => $this->contentPreview,
        ];
    }
}
