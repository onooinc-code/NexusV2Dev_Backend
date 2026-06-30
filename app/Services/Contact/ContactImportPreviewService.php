<?php

namespace App\Services\Contact;

use App\Models\Contact;

class ContactImportPreviewService
{
    protected ContactImportPipeline $pipeline;

    public function __construct(ContactImportPipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function preview(
        Contact $contact,
        string $source,
        string $content,
        string $format,
        ?string $timezone = 'UTC'
    ): array {
        try {
            $parsedMessages = $this->pipeline->parse($source, $content, $format, $contact, $timezone);

            return [
                'success' => true,
                'source' => $source,
                'format' => $format,
                'total_messages' => count($parsedMessages),
                'message_sample' => array_slice($parsedMessages, 0, 3),
                'inbound_count' => count(array_filter($parsedMessages, fn($m) => $m['direction'] === 'inbound')),
                'outbound_count' => count(array_filter($parsedMessages, fn($m) => $m['direction'] === 'outbound')),
                'date_range' => $this->pipeline->getDateRange($parsedMessages),
                'unique_senders' => count(array_unique(array_column($parsedMessages, 'sender_identifier'))),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
