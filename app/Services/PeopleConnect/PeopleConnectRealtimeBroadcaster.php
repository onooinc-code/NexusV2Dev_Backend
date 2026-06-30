<?php

namespace App\Services\PeopleConnect;

use App\Models\PeopleConnect\PeopleConnectMessage;
use App\Models\PeopleConnect\PeopleConnectMessageAnalysis;
use App\Models\PeopleConnect\PeopleConnectSession;
use App\Models\PeopleConnect\PeopleConnectReplyDraft;
use App\Events\PeopleConnect\MessageReceived;
use App\Events\PeopleConnect\MessageAnalyzed;
use App\Events\PeopleConnect\MessageDelivered;
use App\Events\PeopleConnect\MessageFailed;
use App\Events\PeopleConnect\SessionOpened;
use App\Events\PeopleConnect\SessionClosed;
use App\Events\PeopleConnect\ReplyDraftCreated;
use App\Events\PeopleConnect\AutopilotBlocked;

class PeopleConnectRealtimeBroadcaster
{
    public function messageReceived(PeopleConnectMessage $message): void
    {
        event(new MessageReceived($message));
    }

    public function messageAnalyzed(PeopleConnectMessage $message, PeopleConnectMessageAnalysis $analysis): void
    {
        event(new MessageAnalyzed($message, $analysis));
    }

    public function messageDelivered(PeopleConnectMessage $message): void
    {
        event(new MessageDelivered($message));
    }

    public function messageFailed(PeopleConnectMessage $message, string $reason = ''): void
    {
        event(new MessageFailed($message, $reason));
    }

    public function sessionOpened(PeopleConnectSession $session): void
    {
        event(new SessionOpened($session));
    }

    public function sessionClosed(PeopleConnectSession $session): void
    {
        event(new SessionClosed($session));
    }

    public function replyDraftCreated(PeopleConnectReplyDraft $draft): void
    {
        event(new ReplyDraftCreated($draft));
    }

    public function autopilotBlocked(int $conversationId, string $reason): void
    {
        event(new AutopilotBlocked($conversationId, $reason));
    }
}

