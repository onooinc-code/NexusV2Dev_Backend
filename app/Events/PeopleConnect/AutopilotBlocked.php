<?php

namespace App\Events\PeopleConnect;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\PeopleConnect\PeopleConnectProcessingLog;

class AutopilotBlocked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public string $reason
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('peopleconnect.conversation.' . $this->conversationId)];
    }

    public function broadcastAs(): string { return 'autopilot.blocked'; }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'reason' => $this->reason,
        ];
    }
}
