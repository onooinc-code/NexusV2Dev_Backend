<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/v1/login', 'POST', ['email' => 'admin@nexus.local', 'password' => 'password123']);
$response = $kernel->handle($request);

echo $response->getStatusCode() . "\n";
echo $response->getContent() . "\n";
