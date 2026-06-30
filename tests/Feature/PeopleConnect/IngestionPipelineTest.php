<?php

namespace Tests\Feature\PeopleConnect;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use App\Services\PeopleConnect\WahaWebhookIngestionService;
use App\Models\PeopleConnect\PeopleConnectRawProviderEvent;
use App\Models\Contact;
use App\Models\PeopleConnect\PeopleConnectConversation;
use App\Models\PeopleConnect\PeopleConnectSession;
use App\Models\PeopleConnect\PeopleConnectMessage;
use App\Jobs\ProcessWahaWebhookJob;
use App\Jobs\PeopleConnect\AnalyzePeopleConnectMessageJob;
use Carbon\Carbon;

class IngestionPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingestion_service_deduplicates_raw_events_and_dispatches_job()
    {
        Queue::fake();

        $service = new WahaWebhookIngestionService();
        $payload = [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'msg_123',
                'chatId' => '12345@c.us',
                'from' => '12345@c.us',
                'body' => 'Hello',
                'timestamp' => time(),
            ]
        ];

        // First ingestion
        $service->ingest($payload);

        $this->assertDatabaseCount('peopleconnect_raw_provider_events', 1);
        Queue::assertPushed(ProcessWahaWebhookJob::class, 1);

        // Second ingestion with same session and payload.id
        $service->ingest($payload);

        // Should not create another raw event or dispatch another job
        $this->assertDatabaseCount('peopleconnect_raw_provider_events', 1);
        Queue::assertPushed(ProcessWahaWebhookJob::class, 1);
    }

    public function test_process_job_creates_contact_conversation_session_and_message()
    {
        Queue::fake([AnalyzePeopleConnectMessageJob::class]);

        $payload = [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'msg_456',
                'chatId' => '98765@c.us',
                'from' => '98765@c.us',
                'pushname' => 'John Doe',
                'body' => 'Test message',
                'timestamp' => Carbon::now()->timestamp,
            ]
        ];

        $rawEvent = PeopleConnectRawProviderEvent::create([
            'event_type' => 'message',
            'payload' => $payload,
            'session_name' => 'default',
            'received_at' => now(),
            'processing_status' => 'pending',
        ]);

        $job = new ProcessWahaWebhookJob($payload, $rawEvent->id);
        $job->handle(
            app(\App\Services\PeopleConnect\PeopleConnectContactResolver::class),
            app(\App\Services\PeopleConnect\PeopleConnectConversationService::class),
            app(\App\Services\PeopleConnect\PeopleConnectSessionService::class),
            app(\App\Services\PeopleConnect\PeopleConnectMessageService::class),
            app(\App\Services\PeopleConnect\PeopleConnectRealtimeBroadcaster::class)
        );

        // Assert Contact created
        $this->assertDatabaseHas('contacts', [
            'name' => 'John Doe',
            'whatsapp_number' => '98765'
        ]);
        $contact = Contact::where('whatsapp_number', '98765')->first();

        // Assert Conversation created
        $this->assertDatabaseHas('peopleconnect_conversations', [
            'contact_id' => $contact->id,
            'channel' => 'whatsapp',
            'provider_conversation_id' => '98765@c.us',
            'unread_count' => 1,
            'last_message_preview' => 'Test message'
        ]);
        $conversation = PeopleConnectConversation::where('contact_id', $contact->id)->first();

        // Assert Session created
        $this->assertDatabaseHas('peopleconnect_sessions', [
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'status' => 'open',
            'message_count' => 1
        ]);
        $session = PeopleConnectSession::where('conversation_id', $conversation->id)->first();

        // Assert Message created
        $this->assertDatabaseHas('peopleconnect_messages', [
            'conversation_id' => $conversation->id,
            'session_id' => $session->id,
            'contact_id' => $contact->id,
            'sender_type' => 'contact',
            'direction' => 'inbound',
            'body' => 'Test message',
            'waha_message_id' => 'msg_456'
        ]);

        // Assert Raw Event status updated
        $this->assertDatabaseHas('peopleconnect_raw_provider_events', [
            'id' => $rawEvent->id,
            'processing_status' => 'processed'
        ]);

        // Assert analysis job dispatched
        Queue::assertPushed(AnalyzePeopleConnectMessageJob::class);
    }
    
    public function test_process_job_deduplicates_messages()
    {
        Queue::fake([AnalyzePeopleConnectMessageJob::class]);

        $payload = [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'msg_789',
                'chatId' => '11223@c.us',
                'from' => '11223@c.us',
                'body' => 'Hello again',
                'timestamp' => Carbon::now()->timestamp,
            ]
        ];

        $job = new ProcessWahaWebhookJob($payload, null);
        $job->handle(
            app(\App\Services\PeopleConnect\PeopleConnectContactResolver::class),
            app(\App\Services\PeopleConnect\PeopleConnectConversationService::class),
            app(\App\Services\PeopleConnect\PeopleConnectSessionService::class),
            app(\App\Services\PeopleConnect\PeopleConnectMessageService::class),
            app(\App\Services\PeopleConnect\PeopleConnectRealtimeBroadcaster::class)
        );

        $this->assertDatabaseCount('peopleconnect_messages', 1);

        // Run same job again (e.g. queue retry despite raw dedup)
        $job->handle(
            app(\App\Services\PeopleConnect\PeopleConnectContactResolver::class),
            app(\App\Services\PeopleConnect\PeopleConnectConversationService::class),
            app(\App\Services\PeopleConnect\PeopleConnectSessionService::class),
            app(\App\Services\PeopleConnect\PeopleConnectMessageService::class),
            app(\App\Services\PeopleConnect\PeopleConnectRealtimeBroadcaster::class)
        );

        // Message count should still be 1 (DuplicateMessageException caught silently in handle)
        $this->assertDatabaseCount('peopleconnect_messages', 1);
        
        // Processing log should have dedup_skipped
        $this->assertDatabaseHas('peopleconnect_processing_logs', [
            'event_type' => 'dedup_skipped'
        ]);
    }
}
