<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class HorizonAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Force environment to production to test the gate logic
        // since Horizon allows all in local environment by default.
        Config::set('app.env', 'production');
    }

    public function test_guest_cannot_access_horizon(): void
    {
        $response = $this->get('/horizon');

        // By default, Horizon returns 403 for unauthorized guests/users in non-local environments
        $response->assertStatus(403);
    }

    public function test_authenticated_user_without_authorized_email_cannot_access_horizon(): void
    {
        // Set authorized emails
        putenv('HORIZON_AUTHORIZED_EMAILS=admin@example.com');
        
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->actingAs($user)->get('/horizon');

        $response->assertStatus(403);
    }

    public function test_authenticated_user_with_authorized_email_can_access_horizon(): void
    {
        // Set authorized emails
        putenv('HORIZON_AUTHORIZED_EMAILS=admin@example.com,user@example.com');
        
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $response = $this->actingAs($user)->get('/horizon');

        // Horizon routes typically return 200 OK when authorized
        $response->assertStatus(200);
    }
}
