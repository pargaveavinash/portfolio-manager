<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear rate limiters and cache to ensure clean state per test
        RateLimiter::clear('auth');
        RateLimiter::clear('api');
        RateLimiter::clear('api_expensive');
        RateLimiter::clear('health');
        Cache::flush();
    }

    public function test_auth_endpoints_are_rate_limited_to_5_per_minute_by_ip()
    {
        $payload = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        // Make 5 requests, which should be allowed
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login', $payload);
            $response->assertStatus(401);
            $response->assertHeader('X-RateLimit-Limit', 5);
            $response->assertHeader('X-RateLimit-Remaining', 5 - $i);
        }

        // 6th request should hit the rate limit
        $response = $this->postJson('/api/v1/auth/login', $payload);
        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
        $response->assertHeader('X-RateLimit-Limit', 5);
        $response->assertHeader('X-RateLimit-Remaining', 0);
    }

    public function test_expensive_api_endpoints_are_rate_limited_to_15_per_minute_by_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Make 15 requests, which should be allowed
        for ($i = 1; $i <= 15; $i++) {
            $response = $this->getJson('/api/v1/mutual-funds/search?query=test');
            $response->assertStatus(200);
            $response->assertHeader('X-RateLimit-Limit', 15);
            $response->assertHeader('X-RateLimit-Remaining', 15 - $i);
        }

        // 16th request should hit the rate limit
        $response = $this->getJson('/api/v1/mutual-funds/search?query=test');
        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
        $response->assertHeader('X-RateLimit-Limit', 15);
        $response->assertHeader('X-RateLimit-Remaining', 0);
    }

    public function test_standard_api_endpoints_are_rate_limited_to_60_per_minute_by_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Make 60 requests, which should be allowed
        for ($i = 1; $i <= 60; $i++) {
            $response = $this->getJson('/api/v1/portfolios');
            $response->assertStatus(200);
            $response->assertHeader('X-RateLimit-Limit', 60);
            $response->assertHeader('X-RateLimit-Remaining', 60 - $i);
        }

        // 61st request should hit the rate limit
        $response = $this->getJson('/api/v1/portfolios');
        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
        $response->assertHeader('X-RateLimit-Limit', 60);
        $response->assertHeader('X-RateLimit-Remaining', 0);
    }

    public function test_health_endpoints_are_rate_limited_to_120_per_minute_by_ip()
    {
        // Make 120 requests, which should be allowed
        for ($i = 1; $i <= 120; $i++) {
            $response = $this->getJson('/api/v1/health');
            $response->assertStatus(200);
            $response->assertHeader('X-RateLimit-Limit', 120);
            $response->assertHeader('X-RateLimit-Remaining', 120 - $i);
        }

        // 121st request should hit the rate limit
        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
        $response->assertHeader('X-RateLimit-Limit', 120);
        $response->assertHeader('X-RateLimit-Remaining', 0);
    }

    public function test_rate_limits_are_isolated_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1 uses all their expensive requests
        $this->actingAs($user1);
        for ($i = 0; $i < 15; $i++) {
            $this->getJson('/api/v1/mutual-funds/search')->assertStatus(200);
        }
        $this->getJson('/api/v1/mutual-funds/search')->assertStatus(429);

        // User 2 should still have their full quota
        $this->actingAs($user2);
        $this->getJson('/api/v1/mutual-funds/search')->assertStatus(200);
    }

    public function test_rate_limits_are_isolated_by_ip_for_anonymous_requests()
    {
        // IP 1 uses all their auth requests
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '1.1.1.1'])
                 ->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'pass'])
                 ->assertStatus(401);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '1.1.1.1'])
             ->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'pass'])
             ->assertStatus(429);

        // IP 2 should still have their full quota
        $this->withServerVariables(['REMOTE_ADDR' => '2.2.2.2'])
             ->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'pass'])
             ->assertStatus(401);
    }
}
