<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$batch = App\Models\ContactImportBatch::find(4);
$pipeline = app(App\Services\Contact\ContactImportPipeline::class);
dump($pipeline->commit($batch, $batch->metadata['content'] ?? '', 'txt', 'UTC'));
