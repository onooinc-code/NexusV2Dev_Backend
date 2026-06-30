<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MetricsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $total_contacts;
    public $active_tasks;
    public $agent_count;
    public $memory_count;

    /**
     * Create a new event instance.
     */
    public function __construct($total_contacts = null, $active_tasks = null, $agent_count = null, $memory_count = null)
    {
        $this->total_contacts = $total_contacts;
        $this->active_tasks = $active_tasks;
        $this->agent_count = $agent_count;
        $this->memory_count = $memory_count;
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
