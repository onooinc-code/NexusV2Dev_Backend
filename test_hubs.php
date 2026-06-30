<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Login first
$loginReq = Illuminate\Http\Request::create('/api/v1/login', 'POST', ['email' => 'admin@nexus.local', 'password' => 'password123']);
$loginRes = $kernel->handle($loginReq);
$token = json_decode($loginRes->getContent())->access_token;

$hubs = [
    '/api/v1/agents',
    '/api/v1/agent-personas',
    '/api/v1/mcp-servers',
    '/api/v1/tasks',
    '/api/v1/proactive/rules',
    '/api/v1/proactive/triggers',
    '/api/v1/proactive/logs',
    '/api/v1/hedra-soul/status',
    '/api/v1/hedra-soul/sessions',
    '/api/v1/hedra-soul/approvals',
    '/api/v1/contacts',
    '/api/v1/admin/system/status',
    '/api/v1/workflows',
    '/api/v1/notifications',
    '/api/v1/settings',
];

foreach ($hubs as $path) {
    $req = Illuminate\Http\Request::create($path, 'GET');
    $req->headers->set('Authorization', 'Bearer ' . $token);
    $req->headers->set('Accept', 'application/json');
    $res = $kernel->handle($req);
    $status = $res->getStatusCode();
    $icon = $status >= 200 && $status < 300 ? '?' : '?';
    echo $icon . " [$status] $path\n";
    if ($status >= 400) {
        $body = $res->getContent();
        echo "   ERROR: " . substr($body, 0, 200) . "\n";
    }
}
