<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
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

        // Make a request which binds context
        $this->getJson('/api/v1/health', [
            'X-Request-ID' => $clientUuid,
        ]);

        $this->assertEquals($clientUuid, Context::get('request_id'));

        // Verify that Laravel's Context dehydrates the request_id
        // which ContextServiceProvider uses when a job is pushed.
        $dehydrated = Context::dehydrate();

        $this->assertArrayHasKey('request_id', $dehydrated['data'] ?? []);
        $this->assertEquals(serialize($clientUuid), $dehydrated['data']['request_id']);
    }

    public function test_stderr_logging_produces_valid_structured_json(): void
    {
        // This validates the configuration in config/logging.php
        $config = config('logging.channels.stderr');

        $this->assertEquals('monolog', $config['driver']);
        $this->assertEquals(StreamHandler::class, $config['handler']);
        $this->assertEquals('php://stderr', $config['handler_with']['stream']);

        // Assert the formatter is set to JsonFormatter
        $this->assertEquals(JsonFormatter::class, $config['formatter']);
    }
}
