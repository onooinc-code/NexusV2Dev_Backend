<?php

namespace Tests\Feature\PeopleConnect;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use App\Jobs\ProcessWahaWebhookJob;

class WahaWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Set a dummy webhook secret for testing
        Config::set('services.waha.webhook_secret', 'test-secret-123');
    }

    public function test_rejects_webhook_without_secret()
    {
        $payload = [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'true_1234567890@c.us_3A...',
            ]
        ];

        $response = $this->postJson('/api/v1/webhooks/waha', $payload);

        $response->assertStatus(401);
    }

    public function test_rejects_webhook_with_invalid_secret()
    {
        $payload = [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'true_1234567890@c.us_3A...',
            ]
        ];

        $response = $this->postJson('/api/v1/webhooks/waha', $payload, [
            'x-waha-webhook-secret' => 'wrong-secret'
        ]);

        $response->assertStatus(401);
    }

    public function test_rejects_invalid_payload_structure()
    {
        $payload = [
            'event' => 'message',
            // missing session and payload
        ];

        $response = $this->postJson('/api/v1/webhooks/waha', $payload, [
            'x-waha-webhook-secret' => 'test-secret-123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['session', 'payload']);
    }

    public function test_accepts_valid_webhook_and_dispatches_job()
    {
        Queue::fake();

        $payload = [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'true_1234567890@c.us_3A...',
                'timestamp' => 1610000000,
                'from' => '1234567890@c.us',
                'to' => '0987654321@c.us',
                'body' => 'Hello there',
            ]
        ];

        $response = $this->postJson('/api/v1/webhooks/waha', $payload, [
            'x-waha-webhook-secret' => 'test-secret-123'
        ]);

        $response->assertStatus(202)
                 ->assertJson(['message' => 'Webhook payload queued for processing']);

        Queue::assertPushed(ProcessWahaWebhookJob::class, function ($job) use ($payload) {
            $reflection = new \ReflectionClass($job);
            $property = $reflection->getProperty('payload');
            $property->setAccessible(true);
            $jobPayload = $property->getValue($job);
            
            return $jobPayload['event'] === 'message' &&
                   $jobPayload['session'] === 'default' &&
                   $jobPayload['payload']['id'] === 'true_1234567890@c.us_3A...';
        });
    }

    public function test_accepts_webhook_with_valid_hmac_sha512()
    {
        Queue::fake();

        $payload = [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'true_1234567890@c.us_3A...',
                'timestamp' => 1610000000,
                'from' => '1234567890@c.us',
                'to' => '0987654321@c.us',
                'body' => 'Hello there',
            ]
        ];

        $rawBody = json_encode($payload);
        $signature = hash_hmac('sha512', $rawBody, 'test-secret-123');

        $response = $this->postJson('/api/v1/webhooks/waha', $payload, [
            'x-webhook-hmac' => $signature
        ]);

        $response->assertStatus(202);
    }

    public function test_accepts_webhook_with_valid_hmac_sha256()
    {
        Queue::fake();

        $payload = [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'true_1234567890@c.us_3A...',
                'timestamp' => 1610000000,
                'from' => '1234567890@c.us',
                'to' => '0987654321@c.us',
                'body' => 'Hello there',
            ]
        ];

        $rawBody = json_encode($payload);
        $signature = hash_hmac('sha256', $rawBody, 'test-secret-123');

        $response = $this->postJson('/api/v1/webhooks/waha', $payload, [
            'x-waha-signature' => $signature
        ]);

        $response->assertStatus(202);
    }

    public function test_rejects_webhook_with_invalid_hmac_signature()
    {
        $payload = [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'true_1234567890@c.us_3A...',
            ]
        ];

        $response = $this->postJson('/api/v1/webhooks/waha', $payload, [
            'x-webhook-hmac' => 'invalid-hmac-signature-value'
        ]);

        $response->assertStatus(401);
    }
}
