<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\PeopleConnect\PeopleConnectContactResolver;
use App\Services\PeopleConnect\PeopleConnectConversationService;
use App\Services\PeopleConnect\PeopleConnectSessionService;
use App\Services\PeopleConnect\PeopleConnectMessageService;
use App\Services\PeopleConnect\PeopleConnectRealtimeBroadcaster;
use App\Jobs\PeopleConnect\AnalyzePeopleConnectMessageJob;
use App\Models\PeopleConnect\PeopleConnectRawProviderEvent;
use App\Exceptions\PeopleConnect\DuplicateMessageException;
use Carbon\Carbon;
use Throwable;

class ProcessWahaWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $payload;
    protected ?int $rawEventId;

    /**
     * Create a new job instance.
     *
     * @param array $payload
     * @param int|null $rawEventId
     */
    public function __construct(array $payload, ?int $rawEventId = null)
    {
        $this->payload = $payload;
        $this->rawEventId = $rawEventId;
    }

    /**
     * Execute the job.
     */
    public function handle(
        PeopleConnectContactResolver $contactResolver,
        PeopleConnectConversationService $conversationService,
        PeopleConnectSessionService $sessionService,
        PeopleConnectMessageService $messageService,
        PeopleConnectRealtimeBroadcaster $broadcaster
    ): void {
        $chatId = $this->payload['payload']['chatId'] ?? null;
        $phone = $this->payload['payload']['from'] ?? null;
        $pushName = $this->payload['payload']['pushname'] ?? '';
        $body = $this->payload['payload']['body'] ?? '';
        $timestamp = $this->payload['payload']['timestamp'] ?? time();
        $wahaMessageId = $this->payload['payload']['id'] ?? null;
        
        if (!$chatId || !$phone) {
            $this->markRawEventStatus('error');
            return;
        }
        
        // Strip @c.us suffix if present for phone and chatId
        $phone = str_replace('@c.us', '', $phone);
        
        // 1. Resolve Contact
        $contact = $contactResolver->resolve($chatId, $phone, $pushName);

        // 2. Resolve Conversation
        $conversation = $conversationService->resolveOrCreate($contact->id, 'whatsapp', $chatId);

        // 3. Resolve Session
        $session = $sessionService->resolveOrOpen($conversation, Carbon::createFromTimestamp($timestamp));

        // 4. Insert Message
        try {
            $message = $messageService->insert([
                'conversation_id' => $conversation->id,
                'session_id' => $session->id,
                'contact_id' => $contact->id,
                'sender_type' => 'contact',
                'direction' => 'inbound',
                'body' => $body,
                'status' => 'delivered',
                'waha_message_id' => $wahaMessageId,
                'provider_payload_hash' => hash('sha256', json_encode($this->payload)),
                'delivered_at' => Carbon::createFromTimestamp($timestamp),
            ]);

            // Update conversation last message preview
            $conversation->update([
                'last_message_at' => Carbon::createFromTimestamp($timestamp),
                'last_message_preview' => substr($body, 0, 100),
                'unread_count' => $conversation->unread_count + 1
            ]);
            
            // Update session count
            $session->increment('message_count');

            // 5. Dispatch AnalyzePeopleConnectMessageJob
            AnalyzePeopleConnectMessageJob::dispatch($message);

            // 6. Realtime Broadcasting
            $broadcaster->messageReceived($message);
            
            $this->markRawEventStatus('processed');
            
        } catch (DuplicateMessageException $e) {
            $this->markRawEventStatus('processed');
        } catch (Throwable $e) {
            $this->markRawEventStatus('error');
            throw $e;
        }
    }
    
    private function markRawEventStatus(string $status): void
    {
        if ($this->rawEventId) {
            PeopleConnectRawProviderEvent::where('id', $this->rawEventId)
                ->update([
                    'processed_at' => now(),
                    'processing_status' => $status
                ]);
        }
    }
    
    public function failed(Throwable $exception)
    {
        $this->markRawEventStatus('error');
    }
}
