<?php

namespace App\Events\HedraSoul;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HedraSoulCommandExecuted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public string $traceId,
        public string $selectedAction,
        public ?array $tasksCreated = null,
        public ?array $workflowsTriggered = null
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('hedrasoul.hub.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'hedrasoul.command.executed';
    }

    public function broadcastWith(): array
    {
        return [
            'trace_id' => $this->traceId,
            'selected_action' => $this->selectedAction,
            'tasks_created' => $this->tasksCreated,
            'workflows_triggered' => $this->workflowsTriggered,
        ];
    }
}
