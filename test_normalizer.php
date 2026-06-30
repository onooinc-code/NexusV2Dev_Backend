<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$content = "[2026-05-31, 14:30:45] You: Test message\n[2026-05-31, 14:31:00] Hedra: Another test";
$contact = App\Models\Contact::find(203);
if (!$contact) {
    die("Contact not found!");
}
$parser = new App\Services\Contact\WhatsAppImportParser();
$messages = $parser->parseTxt($content, '+20100000000', 'UTC');
$normalizer = new App\Services\Contact\ContactMessageNormalizer();
$result = $normalizer->normalizeAndCreate($messages, $contact, 'whatsapp', null);
dump($result);
