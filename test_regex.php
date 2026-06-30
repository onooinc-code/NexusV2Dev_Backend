<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$content = "٢١/٠٦/٢٠٢٤, ١٠:٣٠ م - المرسل: رسالة";
$parser = new \App\Services\Contact\WhatsAppImportParser();
$messages = $parser->parseTxt($content, '+20100000000', 'UTC');
dump($messages);
