<?php

namespace App\Events\HedraSoul;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HedraSoulInstructionChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $versionId,
        public int $versionNumber,
        public string $status,
        public ?int $activatedBy = null
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('hedrasoul.hub.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'hedrasoul.instruction.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'version_id' => $this->versionId,
            'version_number' => $this->versionNumber,
            'status' => $this->status,
            'activated_by' => $this->activatedBy,
        ];
    }
}
