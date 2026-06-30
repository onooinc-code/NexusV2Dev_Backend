<?php

namespace App\Events\PeopleConnect;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\PeopleConnect\PeopleConnectMessage;

class MessageDelivered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public PeopleConnectMessage $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('peopleconnect.conversation.' . $this->message->conversation_id)];
    }

    public function broadcastAs(): string { return 'message.delivered'; }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'status' => $this->message->status,
            'delivered_at' => $this->message->delivered_at?->toIso8601String(),
        ];
    }
}
