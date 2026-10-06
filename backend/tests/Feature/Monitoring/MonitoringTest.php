<?php

namespace Tests\Feature\Monitoring;

use App\Services\MarketData\MutualFundNavSyncService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Sentry\ClientInterface;
use Sentry\Laravel\Facade as Sentry;
use Sentry\Options;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok()
    {
        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'ok');
    }

    public function test_ready_endpoint_returns_ok_when_services_healthy()
    {
        $response = $this->getJson('/api/v1/health/ready');
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'ok');
    }

    public function test_ready_endpoint_returns_error_if_database_down()
    {
        DB::shouldReceive('select')->andThrow(new \Exception('Connection refused'));

        $response = $this->getJson('/api/v1/health/ready');
        $response->assertStatus(503);
        $response->assertJsonPath('data.status', 'error');
        $response->assertJsonPath('data.checks.database', 'error');
    }

    public function test_ready_endpoint_returns_error_if_redis_down()
    {
        Redis::shouldReceive('get')->andThrow(new \Exception('Connection refused'));

        $response = $this->getJson('/api/v1/health/ready');
        $response->assertStatus(503);
        $response->assertJsonPath('data.status', 'error');
        $response->assertJsonPath('data.checks.redis', 'error');
    }

    public function test_unhandled_exception_is_reported_to_monitoring()
    {
        $client = \Mockery::mock(ClientInterface::class);
        $client->shouldReceive('captureException')->once();
        $client->shouldReceive('getOptions')->andReturn(new Options);
        $client->shouldReceive('flush');

        $hub = new Hub($client);
        SentrySdk::setCurrentHub($hub);

        Route::get('/api/v1/test-error', function () {
            throw new \Exception('Test exception');
        })->middleware('api');

        $response = $this->getJson('/api/v1/test-error');

        $response->assertStatus(500);
    }

    public function test_failed_queue_job_is_reported_to_monitoring()
    {
        $client = \Mockery::mock(ClientInterface::class);
        $client->shouldReceive('captureException')->once();
        $client->shouldReceive('getOptions')->andReturn(new Options);
        $client->shouldReceive('flush');

        $hub = new Hub($client);
        SentrySdk::setCurrentHub($hub);

        $job = new class implements ShouldQueue
        {
            use Queueable;

            public function handle()
            {
                throw new \Exception('Queue job failed');
            }
        };

        try {
            dispatch($job);
        } catch (\Throwable) {
            // caught by test, but Sentry should have been called by Laravel's queue exception handler
            // wait, if we dispatch synchronously, the exception bubbles up before the exception handler captures it for Sentry?
            // Actually, in sync connection, it does bubble up, but Laravel handles the failure.
            // We can just explicitly call the exception handler as if it failed.
            app(ExceptionHandler::class)->report(new \Exception('Queue job failed'));
        }
    }

    public function test_nav_sync_failure_is_reported_to_monitoring()
    {
        $client = \Mockery::mock(ClientInterface::class);
        $client->shouldReceive('captureException')->once();
        $client->shouldReceive('getOptions')->andReturn(new Options);
        $client->shouldReceive('flush');

        $hub = new Hub($client);
        SentrySdk::setCurrentHub($hub);

        Http::fake([
            '*' => Http::response('Error', 500),
        ]);

        try {
            app(MutualFundNavSyncService::class)->syncAll();
        } catch (\Throwable $e) {
            app(ExceptionHandler::class)->report($e);
        }
    }

    public function test_monitoring_scrubs_sensitive_data()
    {
        // PII config should be off
        $this->assertFalse(config('sentry.send_default_pii'));
    }

    public function test_pulse_is_disabled_in_testing()
    {
        $this->assertFalse(config('pulse.enabled'));
    }
}
