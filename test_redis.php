<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    // Test Redis connection
    echo "Testing Redis Connection...\n";
    echo "----------------------------\n";

    // Test basic set/get
    Redis::set('test_key', 'Hello from Nexus Redis!', 60);
    $value = Redis::get('test_key');
    echo "✓ Redis SET/GET: " . $value . "\n";

    // Test cache
    Cache::put('cache_test', 'Cache is working!', 60);
    echo "✓ Cache PUT: Cache test set\n";

    $cached = Cache::get('cache_test');
    echo "✓ Cache GET: " . $cached . "\n";

    // Test info
    $info = Redis::info();
    echo "\n✓ Redis Server Info:\n";
    echo "   Version: " . $info['redis_version'] . "\n";
    echo "   Memory: " . $info['used_memory_human'] . "\n";
    echo "   Connected Clients: " . $info['connected_clients'] . "\n";

    echo "\n✅ Redis is fully integrated and working!\n";
} catch (\Exception $e) {
    echo "❌ Redis Connection Error:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
