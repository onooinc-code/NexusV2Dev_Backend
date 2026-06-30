<?php

use App\Models\Conversation;
use App\Models\ConversationSession;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('session.{sessionId}', function (User $user, string $sessionId) {
    $session = ConversationSession::with('conversation.contact')->find($sessionId);

    if (! $session || ! $session->conversation) {
        return false;
    }

    return $user->can('subscribe', $session);
});

Broadcast::channel('conversation.{conversationId}', function (User $user, string $conversationId) {
    $conversation = Conversation::with('contact')->find($conversationId);

    return $conversation?->contact?->user_id === $user->id;
});

Broadcast::channel('presence.users.{conversationId}', function (User $user, string $conversationId) {
    $conversation = Conversation::with('contact')->find($conversationId);

    if (! $conversation || $conversation->contact?->user_id !== $user->id) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar_url' => $user->avatar_url ?? null,
    ];
});

Broadcast::channel('job.batch.{batchId}', function (User $user, string $batchId) {
    return in_array($user->email, config('broadcasting.admin_emails', []), true);
});

Broadcast::channel('admin.dlq', function (User $user) {
    return in_array($user->email, config('broadcasting.admin_emails', []), true);
});

Broadcast::channel('workflow.{workflowId}', function (User $user, string $workflowId) {
    $workflow = Workflow::find($workflowId);

    if (! $workflow) {
        return false;
    }

    if (in_array($user->email, config('broadcasting.admin_emails', []), true)) {
        return true;
    }

    return ! $workflow->owner_id || (int) $workflow->owner_id === (int) $user->id;
});

/**
 * PeopleConnect Hub Channels
 */

// Hub-level channel — receives aggregate events (e.g. new message arrives in any conversation)
Broadcast::channel('peopleconnect.hub', function (User $user) {
    // Any authenticated user can subscribe to the hub channel
    return ['id' => $user->id, 'name' => $user->name];
});

// Per-conversation channel — receives detailed events for a specific conversation
Broadcast::channel('peopleconnect.conversation.{conversationId}', function (User $user, int $conversationId) {
    // Any authenticated user may subscribe (multi-tenant ACL can be added here later)
    return ['id' => $user->id, 'name' => $user->name];
});

/**
 * HedraSoul Hub Channels
 */
Broadcast::channel('hedrasoul.hub.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
});
