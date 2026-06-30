<?php

namespace App\Services\HedraSoul;

use App\Models\HedrasoulSession;
use Illuminate\Support\Facades\Auth;

class HedraSoulSessionService
{
    /**
     * Resolve the authenticated user's active HedraSoul session or create one.
     * Returns the most recently active session, or creates a new one if none exists.
     */
    public function resolveOrCreate(): HedrasoulSession
    {
        $session = HedrasoulSession::active()
            ->where('user_id', Auth::id())
            ->orderBy('opened_at', 'desc')
            ->first();

        if (!$session) {
            $session = HedrasoulSession::create([
                'user_id' => Auth::id(),
                'title' => 'Default Session',
                'status' => 'active',
                'opened_at' => now(),
                'task_count' => 0,
                'approval_count' => 0,
                'topic' => null,
                'summary' => null,
            ]);
        }

        return $session;
    }

    /**
     * Create a new named HedraSoul session for the authenticated user.
     */
    public function createNamed(string $title): HedrasoulSession
    {
        return HedrasoulSession::create([
            'user_id' => Auth::id(),
            'title' => $title,
            'status' => 'active',
            'opened_at' => now(),
            'task_count' => 0,
            'approval_count' => 0,
            'topic' => null,
            'summary' => null,
        ]);
    }

    /**
     * Archive a HedraSoul session, moving it to inactive state.
     */
    public function archive(HedrasoulSession $session): void
    {
        $session->update(['status' => 'archived']);
    }

    /**
     * Restore an archived HedraSoul session back to active state.
     */
    public function restore(HedrasoulSession $session): void
    {
        $session->update(['status' => 'active', 'opened_at' => now()]);
    }

    /**
     * Close an inactive HedraSoul session (called by scheduler job after 2-hour inactivity).
     */
    public function close(HedrasoulSession $session): void
    {
        $session->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * Dispatch job to generate and store a session summary via AiModelsHub.
     */
    public function summarize(HedrasoulSession $session): void
    {
        // This would dispatch an async job to generate session summary
        // dispatch(new \App\Jobs\HedraSoul\GenerateSessionSummaryJob($session));
    }

    /**
     * Update session metadata (title, topic, task_count, approval_count).
     */
    public function updateSession(HedrasoulSession $session, array $data): HedrasoulSession
    {
        $session->update($data);
        return $session;
    }

    public function startSession(array $data = []): HedrasoulSession
    {
        return HedrasoulSession::create([
            'user_id' => Auth::id(),
            'title' => $data['title'] ?? 'Session ' . now()->toDateTimeString(),
            'status' => 'active',
            'opened_at' => now(),
            'last_autonomy_mode' => $data['mode'] ?? null,
        ]);
    }

    public function getCurrentSession(): ?HedrasoulSession
    {
        return HedrasoulSession::active()
            ->where('user_id', Auth::id())
            ->orderBy('opened_at', 'desc')
            ->first();
    }
}

