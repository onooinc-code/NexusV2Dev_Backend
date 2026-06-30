<?php

namespace App\Services\PeopleConnect;

use App\Models\PeopleConnect\PeopleConnectMessage;
use App\Models\PeopleConnect\PeopleConnectDeliveryAttempt;
use Illuminate\Support\Facades\Http;

class WahaMessageDispatcher
{
    public function send(PeopleConnectMessage $message): void
    {
        $wahaUrl = app(\App\Services\SettingCacheService::class)->get('waha_url', config('services.waha.url', 'http://waha:3000'));
        $wahaSecret = app(\App\Services\SettingCacheService::class)->get('waha_api_key', config('services.waha.api_key', ''));
        
        $conversation = $message->conversation;
        $chatId = $conversation->provider_conversation_id;

        $attempt = PeopleConnectDeliveryAttempt::create([
            'message_id' => $message->id,
            'status' => 'sending',
            'attempted_at' => now(),
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$wahaSecret}"
            ])->post("{$wahaUrl}/api/sendText", [
                'session' => 'default',
                'chatId' => $chatId,
                'text' => $message->body,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $attempt->update([
                    'status' => 'delivered',
                    'provider_response' => $data,
                ]);
                
                $message->update([
                    'status' => 'delivered',
                    'waha_message_id' => $data['id'] ?? null,
                    'delivered_at' => now(),
                ]);
            } else {
                throw new \Exception("WAHA API Error: " . $response->body());
            }
        } catch (\Throwable $e) {
            $attempt->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            
            $message->update(['status' => 'failed']);
            throw $e;
        }
    }
}
