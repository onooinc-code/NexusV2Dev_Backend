<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);

\Illuminate\Support\Facades\Artisan::call('migrate:fresh');

$workflow = \App\Models\Workflow::factory()->create([
    'steps' => [
        ['name' => 'Step 1', 'action' => 'log', 'message' => 'Step 1'],
        ['name' => 'Step 2', 'action' => 'log', 'message' => 'Step 2'],
    ],
]);

$raw = \Illuminate\Support\Facades\DB::table('workflows')->first();
var_dump($raw->steps);
