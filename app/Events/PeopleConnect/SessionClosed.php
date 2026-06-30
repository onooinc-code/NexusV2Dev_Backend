<?php

namespace App\Events\PeopleConnect;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\PeopleConnect\PeopleConnectSession;

class SessionClosed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public PeopleConnectSession $session) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('peopleconnect.conversation.' . $this->session->conversation_id)];
    }

    public function broadcastAs(): string { return 'session.closed'; }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'conversation_id' => $this->session->conversation_id,
            'closed_reason' => $this->session->closed_reason,
            'closed_at' => $this->session->closed_at?->toIso8601String(),
        ];
    }
}
