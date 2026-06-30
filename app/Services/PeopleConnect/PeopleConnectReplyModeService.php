<?php

namespace App\Services\PeopleConnect;

use App\Models\PeopleConnect\PeopleConnectConversation;
use App\Models\Setting;
use App\Models\PeopleConnect\PeopleConnectMessage;
use App\Models\PeopleConnect\PeopleConnectProcessingLog;
use Carbon\Carbon;

class PeopleConnectReplyModeService
{
    /**
     * Resolves the effective reply mode for a contact.
     * Checks conversation override first, then falls back to global setting.
     *
     * @param int $contactId
     * @return string
     */
    public function resolveEffectiveMode(int $contactId): string
    {
        $conversation = PeopleConnectConversation::where('contact_id', $contactId)
            ->where('channel', 'whatsapp')
            ->first();

        if ($conversation && $conversation->reply_mode_effective) {
            return $conversation->reply_mode_effective;
        }
        
        // Check overrides table
        $override = \DB::table('peopleconnect_reply_mode_overrides')
            ->where('contact_id', $contactId)
            ->first();
            
        if ($override && $override->mode) {
            return $override->mode;
        }

        // Fall back to global setting
        $globalSetting = Setting::where('key', 'peopleconnect.reply_mode.global')->first();
        return $globalSetting ? $globalSetting->value : 'manual';
    }

    /**
     * Checks autopilot safety conditions before sending a message.
     * 
     * @param int $contactId
     * @param PeopleConnectMessage $trigger
     * @return array ['blocked' => bool, 'reason' => string|null]
     */
    public function checkAutopilotSafety(int $contactId, PeopleConnectMessage $trigger): array
    {
        // 1. Check if effective mode is autopilot
        $effectiveMode = $this->resolveEffectiveMode($contactId);
        if ($effectiveMode !== 'autopilot') {
            return ['blocked' => true, 'reason' => 'Mode is not autopilot'];
        }

        // 2. Rate Limit (e.g. no more than 5 msgs in 5 mins)
        $recentMsgsCount = PeopleConnectMessage::where('contact_id', $contactId)
            ->where('sender_type', 'agent')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();
            
        if ($recentMsgsCount >= 5) {
            $this->logBlock($trigger, 'rate_limit_exceeded');
            return ['blocked' => true, 'reason' => 'Rate limit exceeded'];
        }

        // 3. (Other safety conditions as required by design...)
        
        return ['blocked' => false, 'reason' => null];
    }
    
    protected function logBlock(PeopleConnectMessage $message, string $reason): void
    {
        PeopleConnectProcessingLog::create([
            'conversation_id' => $message->conversation_id,
            'event_type' => 'autopilot_blocked',
            'description' => "Autopilot blocked due to: {$reason}",
            'payload' => ['reason' => $reason, 'message_id' => $message->id]
        ]);
        
        // Broadcast phase 7...
    }
}
