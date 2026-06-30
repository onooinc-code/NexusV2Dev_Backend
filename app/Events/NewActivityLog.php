<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewActivityLog implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $level;
    public $message;
    public $channel;

    /**
     * Create a new event instance.
     */
    public function __construct($level, $message, $channel = 'system')
    {
        $this->level = $level;
        $this->message = $message;
        $this->channel = $channel;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('nexus-system'),
        ];
    }
}
