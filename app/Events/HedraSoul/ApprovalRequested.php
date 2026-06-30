<?php

namespace App\Events\HedraSoul;

use App\Models\HedrasoulApprovalRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public HedrasoulApprovalRequest $request;

    public function __construct(HedrasoulApprovalRequest $request)
    {
        $this->request = $request;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('hedrasoul.hub.1');
    }

    public function broadcastAs()
    {
        return 'approval.requested';
    }
}
