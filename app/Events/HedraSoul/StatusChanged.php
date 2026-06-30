<?php

namespace App\Events\HedraSoul;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $status;
    public int $userId;

    public function __construct(string $status, int $userId)
    {
        $this->status = $status;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('hedrasoul.hub.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'status.changed';
    }
}
