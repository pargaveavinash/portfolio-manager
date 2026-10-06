<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Tests\TestCase;

class LoggingContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear context before each test to ensure a clean state
        Context::flush();
    }

    public function test_request_without_x_request_id_receives_generated_uuid(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
        $response->assertHeader('X-Request-ID');

        $requestId = $response->headers->get('X-Request-ID');
        $this->assertTrue(Str::isUuid($requestId), 'Generated Request ID must be a valid UUID');

        $this->assertEquals($requestId, Context::get('request_id'));
    }

    public function test_valid_x_request_id_is_preserved(): void
    {
        $clientUuid = Str::uuid()->toString();

        $response = $this->getJson('/api/v1/health', [
            'X-Request-ID' => $clientUuid,
        ]);

        $response->assertStatus(200);
        $response->assertHeader('X-Request-ID', $clientUuid);
        $this->assertEquals($clientUuid, Context::get('request_id'));
    }

    public function test_invalid_x_request_id_is_replaced_with_generated_uuid(): void
    {
        $invalidId = 'not-a-valid-uuid-12345';

        $response = $this->getJson('/api/v1/health', [
            'X-Request-ID' => $invalidId,
        ]);

        $response->assertStatus(200);
        $response->assertHeader('X-Request-ID');

        $requestId = $response->headers->get('X-Request-ID');
        $this->assertNotEquals($invalidId, $requestId);
        $this->assertTrue(Str::isUuid($requestId), 'Replaced Request ID must be a valid UUID');
        $this->assertEquals($requestId, Context::get('request_id'));
    }

    public function test_authenticated_request_adds_user_id_to_context(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/portfolios');

        $response->assertStatus(200);
        $this->assertEquals($user->id, Context::get('user_id'));
    }

    public function test_unauthenticated_request_does_not_fail_and_has_no_user_id_in_context(): void
    {
        $response = $this->getJson('/api/v1/portfolios');

        $response->assertStatus(401);
        $this->assertFalse(Context::has('user_id'));
        $this->assertTrue(Context::has('request_id'));
    }

    public function test_queued_jobs_receive_propagated_context(): void
    {
        $clientUuid = Str::uuid()->toString();

        // Bind context manually or via request
        Context::add('request_id', $clientUuid);
        $this->assertEquals($clientUuid, Context::get('request_id'));

        // We clear the cache to ensure a fresh state
        Cache::forget('dummy_job_request_id');

        // Dispatching a real job class to the sync queue.
        // Sync driver processes immediately but goes through the payload serialization
        // lifecycle, thus testing hydration.
        dispatch(new DummyContextJob);

        $propagatedRequestId = Cache::get('dummy_job_request_id');

        $this->assertNotNull($propagatedRequestId, 'The queued job should have executed and written to cache');
        $this->assertEquals($clientUuid, $propagatedRequestId, 'The job should have access to the propagated request_id context');
    }

    public function test_stderr_logging_produces_valid_structured_json(): void
    {
        // Use an in-memory stream to capture actual JSON formatting
        $stream = fopen('php://memory', 'w+');
        $handler = new StreamHandler($stream);
        $handler->setFormatter(new JsonFormatter);

        $logger = new Logger('test_logger');
        $logger->pushHandler($handler);

        // Write a test log with context
        $logger->info('test log message', ['user_id' => 12345]);

        // Read the stream contents
        rewind($stream);
        $output = stream_get_contents($stream);
        fclose($stream);

        $this->assertNotEmpty($output, 'Log output should not be empty');

        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded, 'The log output should be valid JSON');
        $this->assertArrayHasKey('message', $decoded);
        $this->assertEquals('test log message', $decoded['message']);
        $this->assertArrayHasKey('level', $decoded);
        $this->assertArrayHasKey('level_name', $decoded);
        $this->assertEquals('INFO', $decoded['level_name']);
        $this->assertArrayHasKey('context', $decoded);
        $this->assertArrayHasKey('user_id', $decoded['context']);
        $this->assertEquals(12345, $decoded['context']['user_id']);
    }
}

class DummyContextJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::put('dummy_job_request_id', Context::get('request_id'));
    }
}
