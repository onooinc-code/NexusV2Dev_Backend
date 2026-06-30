<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Cache;

echo "Checking Redis Cache Contents:\n";
echo "==============================\n\n";

// Check if the admin system status cache exists
$cacheKey = 'admin:system:status';
$cached = Cache::get($cacheKey);

if ($cached) {
    echo "✅ Cache key found: $cacheKey\n\n";
    echo "Cache contents (first 500 chars):\n";
    $preview = json_encode($cached, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (strlen($preview) > 500) {
        echo substr($preview, 0, 500) . "...\n";
    } else {
        echo $preview . "\n";
    }
} else {
    echo "❌ Cache key NOT found: $cacheKey\n";
    echo "   (Cache may have expired or not been set yet)\n";
}

// List all cache keys
echo "\n\nAll Cache Keys (Redis):\n";
echo "----------------------\n";

try {
    // This is a simple way to check what's in Redis
    $allKeys = Cache::store('redis')->connection()->keys('*');

    if (empty($allKeys)) {
        echo "   (No keys found in Redis)\n";
    } else {
        foreach ($allKeys as $key) {
            $value = Cache::get(str_replace('laravel_database_', '', $key));
            echo "   • $key\n";
        }
    }
} catch (\Exception $e) {
    echo "   (Could not list keys: " . $e->getMessage() . ")\n";
}

echo "\n✅ Redis Cache Check Complete\n";
