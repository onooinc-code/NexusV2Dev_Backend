<?php

namespace App\Events\HedraSoul;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HedraSoulCommandDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $messageId,
        public string $intent,
        public string $riskLevel,
        public array $policyResult
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('hedrasoul.hub.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'hedrasoul.command.detected';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'intent' => $this->intent,
            'risk_level' => $this->riskLevel,
            'policy_result' => $this->policyResult,
        ];
    }
}
