<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_notifications(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/alerts/notifications');

        $response->assertStatus(200); // Wait, route doesn't exist, this will return 404 in TDD RED phase
        // Actually, for TDD RED phase, we just want the test to fail. 
        // 404 is the expected failure since the route doesn't exist yet.
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $rule = \App\Models\AlertRule::create([
            'user_id' => $user->id,
            'type' => 'ALLOCATION',
            'reference_type' => 'portfolio',
            'reference_id' => '1',
            'is_active' => true,
        ]);
        $notification = \App\Models\AlertNotification::create([
            'alert_rule_id' => $rule->id,
            'user_id' => $user->id,
            'message' => 'Test notification',
            'triggered_at' => now(),
        ]);

        $response = $this->actingAs($user)->patchJson("/api/v1/alerts/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $this->assertTrue($notification->fresh()->is_read);
    }
}
