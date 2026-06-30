<?php

namespace App\Events\HedraSoul;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HedraSoulApprovalApproved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $approvalId,
        public ?int $decidedBy = null,
        public ?string $decidedAt = null
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('hedrasoul.hub.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'hedrasoul.approval.approved';
    }

    public function broadcastWith(): array
    {
        return [
            'approval_id' => $this->approvalId,
            'decided_by' => $this->decidedBy,
            'decided_at' => $this->decidedAt,
        ];
    }
}
