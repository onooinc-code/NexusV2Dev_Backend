<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Workflow;

putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);

\Illuminate\Support\Facades\Artisan::call('migrate:fresh');

$workflow = Workflow::factory()->create([
    'steps' => [
        ['name' => 'Step 1', 'action' => 'log', 'message' => 'Step 1'],
        ['name' => 'Step 2', 'action' => 'log', 'message' => 'Step 2'],
    ]
]);

$fresh = $workflow->fresh();
$arr2 = $fresh->toArray();
echo "From DB:\n";
var_dump($arr2['steps'] ?? null);
var_dump(is_array($arr2['steps']));

$reg = app(\App\Services\Workflows\WorkflowRegistry::class);
try {
    $reg->getExecutableVersion($fresh);
    echo "Executable Version created successfully!\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
