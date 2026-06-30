<?php 
require 'vendor/autoload.php'; 
$app = require_once 'bootstrap/app.php'; 
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap(); 
$url=app(\App\Services\SettingCacheService::class)->get('waha_url', 'http://127.0.0.1:3000'); 
$key=app(\App\Services\SettingCacheService::class)->get('waha_api_key', '666'); 
$response = Http::withHeaders(['Authorization' => 'Bearer ' . $key, 'X-Api-Key' => $key, 'Accept' => 'application/json'])->get($url.'/api/contacts/all?session=default');
echo $response->status() . "\n" . $response->body() . "\n";
