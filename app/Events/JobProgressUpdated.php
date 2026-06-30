<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $jobId;
    public $type;
    public $progress;
    public $processedItems;
    public $totalItems;
    public $status;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct($jobId, $type, $progress, $processedItems, $totalItems, $status, $message = '')
    {
        $this->jobId = $jobId;
        $this->type = $type;
        $this->progress = $progress;
        $this->processedItems = $processedItems;
        $this->totalItems = $totalItems;
        $this->status = $status;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Public channel for global UI
        return [
            new Channel('system-events'),
        ];
    }
}
