<?php

namespace App\Services\Contact;

use App\Models\Contact;
use App\Models\ContactImportBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContactImportPipeline
{
    protected WhatsAppImportParser $whatsappParser;
    protected FacebookImportParser $facebookParser;
    protected ContactMessageNormalizer $normalizer;
    protected WahaImportService $wahaService;

    public function __construct()
    {
        $this->whatsappParser = new WhatsAppImportParser();
        $this->facebookParser = new FacebookImportParser();
        $this->normalizer = new ContactMessageNormalizer();
        $this->wahaService = new WahaImportService();
    }



    /**
     * Import and commit messages
     *
     * @param ContactImportBatch $batch
     * @param string $content
     * @param string $format
     * @param string|null $timezone
     * @return array
     */
    public function commit(
        ContactImportBatch $batch,
        string $content,
        string $format,
        ?string $timezone = 'UTC'
    ): array {
        try {
            $batch->update(['status' => 'processing']);

            // Parse messages
            $parsedMessages = $this->parse($batch->source, $content, $format, $batch->contact, $timezone);

            // Normalize and create
            $result = $this->normalizer->normalizeAndCreate(
                $parsedMessages,
                $batch->contact,
                $batch->source,
                $batch->id
            );

            // Update batch record
            $batch->update([
                'total_records' => count($parsedMessages),
                'imported_records' => $result['created'],
                'failed_records' => $result['duplicates'] + count($result['errors']),
                'status' => count($result['errors']) > 0 ? 'completed_with_errors' : 'completed',
            ]);

            return [
                'success' => true,
                'batch_id' => $batch->id,
                'created' => $result['created'],
                'duplicates' => $result['duplicates'],
                'errors' => $result['errors'],
                'message' => "Successfully imported {$result['created']} messages",
            ];

        } catch (\Exception $e) {
            $batch->update([
                'status' => 'failed',
                'metadata' => array_merge($batch->metadata ?? [], ['error' => $e->getMessage()]),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'batch_id' => $batch->id,
            ];
        }
    }



    /**
     * Parse import content based on source and format
     *
     * @param string $source
     * @param string $content
     * @param string $format
     * @param Contact $contact
     * @param string|null $timezone
     * @return array
     */
    public function parse(
        string $source,
        string $content,
        string $format,
        Contact $contact,
        ?string $timezone
    ): array {
        return match($source) {
            'whatsapp' => $this->parseWhatsApp($content, $format, $contact, $timezone),
            'whatsapp_waha' => $this->parseWaha($content),
            'facebook' => $this->parseFacebook($content, $format, $timezone),
            default => throw new \InvalidArgumentException("Unknown source: {$source}"),
        };
    }

    /**
     * Parse WAHA live sync format
     */
    private function parseWaha(string $content): array
    {
        $data = json_decode($content, true);
        return $this->wahaService->fetchAndParseMessages($data['session'], $data['chatId'], $data['limit'] ?? 100);
    }

    /**
     * Parse WhatsApp export
     *
     * @param string $content
     * @param string $format
     * @param Contact $contact
     * @param string $timezone
     * @return array
     */
    private function parseWhatsApp(
        string $content,
        string $format,
        Contact $contact,
        string $timezone
    ): array {
        $phoneNumber = $contact->whatsapp_number ?? $contact->phone ?? '';

        return match($format) {
            'txt' => $this->whatsappParser->parseTxt($content, $phoneNumber, $timezone),
            'json' => $this->whatsappParser->parseJson($content, $phoneNumber, $timezone),
            default => throw new \InvalidArgumentException("Unknown WhatsApp format: {$format}"),
        };
    }

    /**
     * Parse Facebook export
     *
     * @param string $content
     * @param string $format
     * @param string $timezone
     * @return array
     */
    private function parseFacebook(
        string $content,
        string $format,
        string $timezone
    ): array {
        return match($format) {
            'txt' => $this->facebookParser->parseTxt($content, $timezone),
            'json' => $this->facebookParser->parseJson($content, $timezone),
            default => throw new \InvalidArgumentException("Unknown Facebook format: {$format}"),
        };
    }

    /**
     * Get date range from messages
     *
     * @param array $messages
     * @return array|null
     */
    public function getDateRange(array $messages): ?array
    {
        if (empty($messages)) {
            return null;
        }

        $timestamps = array_map(fn($m) => strtotime($m['timestamp']), $messages);

        return [
            'earliest' => date('Y-m-d', min($timestamps)),
            'latest' => date('Y-m-d', max($timestamps)),
        ];
    }
}
