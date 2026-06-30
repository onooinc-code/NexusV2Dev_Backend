<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$chatId = '201019688755@c.us';
$wahaUrl = config('services.waha.api_url', 'http://127.0.0.1:3000');
$wahaSecret = app(\App\Services\SettingCacheService::class)->get('waha_api_key')
    ?? config('services.waha.api_key') 
    ?? config('services.waha.api_token')
    ?? '666';

$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => "Bearer {$wahaSecret}",
    'X-Api-Key' => $wahaSecret,
    'Accept' => 'application/json'
])->get("{$wahaUrl}/api/chats/{$chatId}/messages?session=default&limit=2");

dump($wahaSecret);
dump($response->status());
dump($response->json());
