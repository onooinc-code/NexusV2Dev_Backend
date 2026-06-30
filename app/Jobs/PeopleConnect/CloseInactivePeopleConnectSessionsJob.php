<?php

namespace App\Jobs\PeopleConnect;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PeopleConnect\PeopleConnectSession;
use App\Services\PeopleConnect\PeopleConnectRealtimeBroadcaster;
use Throwable;

class CloseInactivePeopleConnectSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PeopleConnectRealtimeBroadcaster $broadcaster): void
    {
        $inactiveTime = now()->subHours(2);
        
        $sessions = PeopleConnectSession::with('conversation')
            ->where('status', 'open')
            ->whereHas('conversation', function ($query) use ($inactiveTime) {
                $query->where('last_message_at', '<', $inactiveTime);
            })
            ->get();
            
        foreach ($sessions as $session) {
            $session->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_reason' => 'inactivity',
            ]);
            
            $broadcaster->sessionClosed($session->refresh());
        }
    }
    
    public function failed(Throwable $exception): void
    {
        \Log::error('CloseInactivePeopleConnectSessionsJob failed: ' . $exception->getMessage());
    }
}
