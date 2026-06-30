<?php

namespace App\Http\Controllers\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\HedrasoulNotification;
use App\Services\HedraSoul\HedraSoulNotificationService;
use Illuminate\Http\Request;

class HedraSoulNotificationController extends Controller
{
    public function __construct(
        protected HedraSoulNotificationService $notificationService
    ) {}

    /**
     * List notifications with optional unread filter
     * GET /hedrasoul/notifications
     */
    public function index(Request $request)
    {
        $query = HedrasoulNotification::query();

        if ($request->boolean('unread')) {
            $query->where('is_read', false)
                ->where('is_dismissed', false);
        } else {
            $query->where('is_dismissed', false);
        }

        $notifications = $query
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($notifications);
    }

    /**
     * Mark a notification as read
     * POST /hedrasoul/notifications/{id}/read
     */
    public function markRead(HedrasoulNotification $notification)
    {
        $this->notificationService->markRead($notification);

        return response()->json([
            'notification_id' => $notification->id,
            'is_read' => true,
        ]);
    }

    /**
     * Snooze a notification until a specified time
     * POST /hedrasoul/notifications/{id}/snooze
     */
    public function snooze(Request $request, HedrasoulNotification $notification)
    {
        $validated = $request->validate([
            'until' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $this->notificationService->snooze($notification, $validated['until']);

        return response()->json([
            'notification_id' => $notification->id,
            'snoozed_until' => $notification->snoozed_until,
        ]);
    }
}
