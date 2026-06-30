<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$workflow = new \App\Models\Workflow([
    'steps' => [
        ['name' => 'Step 1', 'action' => 'log', 'message' => 'Step 1'],
        ['name' => 'Step 2', 'action' => 'log', 'message' => 'Step 2'],
    ]
]);

$arr = $workflow->toArray();
var_dump($arr['steps'] ?? null);
var_dump(is_array($arr['steps'] ?? null));

$contact = \App\Models\Contact::factory()->make();
var_dump($contact->id);

