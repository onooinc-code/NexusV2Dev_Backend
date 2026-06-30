<?php

namespace App\Events\HedraSoul;

use App\Models\HedrasoulMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HedraSoulMessageProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public HedrasoulMessage $message,
        public int $userId
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('hedrasoul.hub.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'hedrasoul.message.processed';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'trace_id' => $this->message->trace_id,
            'intent' => $this->message->intent,
            'status' => $this->message->status,
        ];
    }
}
