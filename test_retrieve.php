<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$workflow = \App\Models\Workflow::first();
if ($workflow) {
    echo "ID: " . $workflow->id . "\n";
    var_dump($workflow->steps);
} else {
    echo "No workflow found in DB.";
}
