<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    // Test user
    User::firstOrCreate(
        ['email' => 'test@example.com'],
        [
            'name' => 'Test User',
            'password' => Hash::make('password123'),
        ]
    );
    echo "✅ Test user created/exists: test@example.com | password: password123\n";

    // Admin user
    User::firstOrCreate(
        ['email' => 'admin@example.com'],
        [
            'name' => 'Admin User',
            'password' => Hash::make('admin123'),
        ]
    );
    echo "✅ Admin user created/exists: admin@example.com | password: admin123\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
