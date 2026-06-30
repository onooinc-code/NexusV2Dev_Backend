<?php

namespace App\Services\HedraSoul;

use App\Models\HedrasoulNotification;

/**
 * HedraSoulNotificationService: Manages HedraSoulHub notifications.
 * Provides creation, read/dismiss/snooze lifecycle management.
 */
class HedraSoulNotificationService
{
    /**
     * Create a new notification.
     */
    public function create(
        string $type,
        string $priority,
        string $title,
        string $body,
        ?int $relatedId = null,
        ?string $relatedType = null,
        ?array $actionButtons = null
    ): HedrasoulNotification {
        $notification = HedrasoulNotification::create([
            'notification_type' => $type,
            'priority' => $priority,
            'title' => $title,
            'body' => $body,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'action_buttons' => $actionButtons ?? [],
            'is_read' => false,
            'is_dismissed' => false,
        ]);

        // Broadcast creation event
        app(HedraSoulRealtimeBroadcaster::class)->broadcastNotificationCreated($notification, auth()->id());

        return $notification;
    }

    /**
     * Mark notification as read.
     */
    public function markRead(HedrasoulNotification $notif): void
    {
        $notif->update(['is_read' => true]);
    }

    /**
     * Snooze a notification (visible again after specified time).
     */
    public function snooze(HedrasoulNotification $notif, string $until): void
    {
        $snoozedUntil = strtotime($until) 
            ? \Carbon\Carbon::createFromTimestamp(strtotime($until))
            : now()->addHours(1);

        $notif->update(['snoozed_until' => $snoozedUntil]);
    }

    /**
     * Dismiss a notification (hide it).
     */
    public function dismiss(HedrasoulNotification $notif): void
    {
        $notif->update(['is_dismissed' => true]);
    }

    /**
     * Get unread notifications for authenticated user.
     */
    public function getUnread($limit = 50)
    {
        return HedrasoulNotification::unread()
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Get active (not dismissed, not snoozed) notifications.
     */
    public function getActive($limit = 50)
    {
        return HedrasoulNotification::active()
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Get all notifications with optional filters.
     */
    public function getNotifications(?string $type = null, ?string $priority = null, $limit = 50)
    {
        $query = HedrasoulNotification::query();

        if ($type) {
            $query->where('notification_type', $type);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): void
    {
        HedrasoulNotification::where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Dismiss all notifications.
     */
    public function dismissAll(): void
    {
        HedrasoulNotification::where('is_dismissed', false)
            ->update(['is_dismissed' => true]);
    }
}
