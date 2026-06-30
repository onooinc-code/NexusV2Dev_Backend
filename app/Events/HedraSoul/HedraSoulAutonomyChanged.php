<?php

namespace App\Events\HedraSoul;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HedraSoulAutonomyChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public string $autonomyMode,
        public ?int $changedBy = null,
        public ?string $changedAt = null
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('hedrasoul.hub.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'hedrasoul.autonomy.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'autonomy_mode' => $this->autonomyMode,
            'changed_by' => $this->changedBy,
            'changed_at' => $this->changedAt ?? now()->toDateTimeString(),
        ];
    }
}
