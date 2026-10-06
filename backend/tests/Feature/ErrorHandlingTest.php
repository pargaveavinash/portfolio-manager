<?php

namespace Tests\Feature;

use App\Exceptions\MissingMarketDataException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_exception_returns_standardized_json()
    {
        Route::get('/_test/missing-market-data', function () {
            throw new MissingMarketDataException('Missing data for ABC');
        });

        $response = $this->getJson('/_test/missing-market-data');

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Market data is temporarily unavailable for one or more holdings.',
                'errors' => [
                    'market_data' => ['Missing data for ABC'],
                ],
            ]);
    }

    public function test_validation_errors_return_standardized_json_422()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/portfolios', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name',
                ],
            ]);
    }

    public function test_unauthenticated_returns_standardized_json_401()
    {
        $response = $this->getJson('/api/v1/portfolios');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_unauthorized_returns_standardized_json_403()
    {
        Route::get('/_test/unauthorized', function () {
            throw new AuthorizationException('This action is unauthorized.');
        });

        $response = $this->getJson('/_test/unauthorized');

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_not_found_returns_standardized_json_404()
    {
        $response = $this->getJson('/api/v1/not-a-real-endpoint');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_method_not_allowed_returns_standardized_json_405()
    {
        $user = User::factory()->create();

        // POST to an endpoint that only accepts GET
        $response = $this->actingAs($user)
            ->postJson('/api/v1/portfolios/1');

        $response->assertStatus(405)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_rate_limiting_returns_standardized_json_429()
    {
        Route::get('/_test/rate-limit', function () {
            return response()->json(['status' => 'ok']);
        })->middleware('throttle:1,1');

        // First request should pass
        $this->getJson('/_test/rate-limit')->assertStatus(200);

        // Second request should fail with 429
        $response = $this->getJson('/_test/rate-limit');

        $response->assertStatus(429)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_production_500_hides_debug_trace()
    {
        // Simulate production environment where debug is false
        Config::set('app.debug', false);

        Route::get('/_test/fatal-error', function () {
            throw new \Exception('Secret database password is password123');
        });

        $response = $this->getJson('/_test/fatal-error');

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Server Error',
            ])
            ->assertJsonMissing([
                'trace',
                'exception',
                'password123',
            ]);
    }

    public function test_sentry_reports_500_exceptions()
    {
        // Simulate production environment
        Config::set('app.debug', false);

        $capturedEvent = null;
        Config::set('sentry.before_send', function (\Sentry\Event $event) use (&$capturedEvent) {
            $capturedEvent = $event;
            return null; // Drop event, do not actually send it
        });

        // Re-init Sentry so it picks up the new config for testing
        app('sentry')->getClient()->getOptions()->setBeforeSendCallback(config('sentry.before_send'));

        Route::get('/_test/fatal-error-sentry', function () {
            throw new \Exception('Sentry testing exception');
        });

        $this->getJson('/_test/fatal-error-sentry')->assertStatus(500);

        $this->assertNotNull($capturedEvent, 'Sentry event was not captured.');

        $exceptions = $capturedEvent->getExceptions();
        $this->assertCount(1, $exceptions);
        $this->assertEquals('Sentry testing exception', $exceptions[0]->getValue());
    }

    public function test_pii_protection_in_exceptions()
    {
        Config::set('app.debug', false);
        Config::set('sentry.send_default_pii', false);

        $capturedEvent = null;
        Config::set('sentry.before_send', function (\Sentry\Event $event) use (&$capturedEvent) {
            $capturedEvent = $event;
            return null;
        });

        app('sentry')->getClient()->getOptions()->setBeforeSendCallback(config('sentry.before_send'));

        Route::post('/_test/fatal-login', function (\Illuminate\Http\Request $request) {
            throw new \Exception('Crash during login');
        });

        $response = $this->postJson('/_test/fatal-login', [
            'password' => 'supersecretpassword123',
            'token' => 'my-auth-token-xyz',
            'password_confirmation' => 'supersecretpassword123'
        ], [
            'Authorization' => 'Bearer sensitive-token-here'
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Server Error'
            ])
            ->assertJsonMissing([
                'supersecretpassword123',
                'my-auth-token-xyz',
                'sensitive-token-here'
            ]);

        $this->assertNotNull($capturedEvent, 'Sentry event was not captured.');

        $requestData = $capturedEvent->getRequest();

        // Check that headers are scrubbed
        $this->assertArrayNotHasKey('Authorization', $requestData['headers'] ?? []);


    }
}
