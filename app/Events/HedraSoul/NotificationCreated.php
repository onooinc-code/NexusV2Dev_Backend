<?php

namespace App\Events\HedraSoul;

use App\Models\HedrasoulNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public HedrasoulNotification $notification;

    public function __construct(HedrasoulNotification $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('hedrasoul.hub.1');
    }

    public function broadcastAs()
    {
        return 'notification.created';
    }
}
