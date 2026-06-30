<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$processes = DB::select('SHOW PROCESSLIST');
foreach ($processes as $p) {
    if ($p->Time > 50 && $p->Id != DB::connection()->getPdo()->query('SELECT CONNECTION_ID()')->fetchColumn()) {
        echo "Killing process {$p->Id}...\n";
        DB::statement("KILL {$p->Id}");
    }
}
echo "Done.\n";
