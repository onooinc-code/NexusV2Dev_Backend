<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonitoringHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_health_endpoint_reports_healthy_when_checks_pass(): void
    {
        $this->withoutExceptionHandling();

        $redis = \Mockery::mock();
        $redis->shouldReceive('ping')->andReturn('PONG');
        $redis->shouldReceive('llen')->with('queues:default')->andReturn(0);
        $redis->shouldReceive('llen')->with('queues:critical')->andReturn(0);
        $redis->shouldReceive('llen')->with('queues:llm-inference')->andReturn(0);
        $redis->shouldReceive('llen')->with('queues:batch')->andReturn(0);

        Redis::shouldReceive('connection')->andReturn($redis);

        config(['services.pinecone.api_key' => 'test-key']);
        config(['database.connections.neo4j.password' => 'test-pass']);
        config(['services.waha.api_token' => 'test-token']);
        config(['services.waha.api_key' => 'test-token']);

        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        $this->mock(\App\Http\Controllers\Monitoring\HealthController::class, function ($mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods();
            $mock->shouldReceive('checkReverb')->andReturn([
                'ok' => true,
                'host' => '127.0.0.1',
                'port' => 6001,
                'status' => 'listening',
            ]);
        });

        $response = $this->getJson('/api/v1/monitoring/health');

        if ($response->json('status') !== 'healthy') {
            dump($response->json());
        }

        $response->assertOk();
        $response->assertJsonPath('status', 'healthy');
        $response->assertJsonPath('checks.redis.ok', true);
        $response->assertJsonPath('checks.reverb.ok', true);
        $response->assertJsonPath('checks.queue.ok', true);
    }
}
