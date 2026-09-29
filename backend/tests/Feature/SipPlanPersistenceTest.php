<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\SipPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SipPlanPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_sip_plan(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson("/api/v1/portfolios/{$portfolio->id}/sip-plans", [
            'amount' => 5000,
            'frequency' => 'monthly',
            'start_date' => Carbon::tomorrow()->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.amount', 5000)
            ->assertJsonPath('data.frequency', 'monthly')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.strategy', 'optimization')
            ->assertJsonPath('data.start_date', Carbon::tomorrow()->toDateString())
            ->assertJsonPath('data.next_scheduled_date', Carbon::tomorrow()->toDateString());

        $this->assertDatabaseHas('sip_plans', [
            'portfolio_id' => $portfolio->id,
            'amount' => 5000,
            'frequency' => 'monthly',
            'status' => 'active',
            'start_date' => Carbon::tomorrow()->startOfDay()->toDateTimeString(),
            'next_scheduled_date' => Carbon::tomorrow()->startOfDay()->toDateTimeString(),
        ]);
    }

    public function test_user_cannot_create_sip_plan_for_others_portfolio(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->postJson("/api/v1/portfolios/{$portfolio->id}/sip-plans", [
            'amount' => 5000,
            'frequency' => 'monthly',
            'start_date' => Carbon::tomorrow()->toDateString(),
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_retrieve_their_sip_plans(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $sipPlan = SipPlan::factory()->create([
            'portfolio_id' => $portfolio->id,
            'amount' => 1000,
            'frequency' => 'weekly',
            'start_date' => Carbon::now()->toDateString(),
            'next_scheduled_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/sip-plans");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $sipPlan->id);
    }

    public function test_user_can_retrieve_specific_sip_plan(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $sipPlan = SipPlan::factory()->create([
            'portfolio_id' => $portfolio->id,
            'amount' => 1000,
            'frequency' => 'weekly',
            'start_date' => Carbon::now()->toDateString(),
            'next_scheduled_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/sip-plans/{$sipPlan->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $sipPlan->id);
    }

    public function test_user_cannot_retrieve_others_sip_plan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $otherUser->id]);

        $sipPlan = SipPlan::factory()->create([
            'portfolio_id' => $portfolio->id,
            'amount' => 1000,
            'frequency' => 'weekly',
            'start_date' => Carbon::now()->toDateString(),
            'next_scheduled_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/sip-plans/{$sipPlan->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_sip_plan(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $sipPlan = SipPlan::factory()->create([
            'portfolio_id' => $portfolio->id,
            'amount' => 1000,
            'frequency' => 'weekly',
            'start_date' => Carbon::now()->toDateString(),
            'next_scheduled_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->putJson("/api/v1/sip-plans/{$sipPlan->id}", [
            'amount' => 2000,
            'frequency' => 'monthly',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.amount', 2000)
            ->assertJsonPath('data.frequency', 'monthly');

        $this->assertDatabaseHas('sip_plans', [
            'id' => $sipPlan->id,
            'amount' => 2000,
            'frequency' => 'monthly',
        ]);
    }

    public function test_user_can_pause_and_resume_sip_plan(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $sipPlan = SipPlan::factory()->create([
            'portfolio_id' => $portfolio->id,
            'amount' => 1000,
            'frequency' => 'weekly',
            'status' => 'active',
            'start_date' => Carbon::now()->toDateString(),
            'next_scheduled_date' => Carbon::now()->toDateString(),
        ]);

        $this->actingAs($user)->putJson("/api/v1/sip-plans/{$sipPlan->id}", [
            'status' => 'paused',
        ])->assertStatus(200);

        $this->assertDatabaseHas('sip_plans', [
            'id' => $sipPlan->id,
            'status' => 'paused',
        ]);

        $this->actingAs($user)->putJson("/api/v1/sip-plans/{$sipPlan->id}", [
            'status' => 'active',
        ])->assertStatus(200);

        $this->assertDatabaseHas('sip_plans', [
            'id' => $sipPlan->id,
            'status' => 'active',
        ]);
    }

    public function test_user_can_delete_sip_plan(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $sipPlan = SipPlan::factory()->create([
            'portfolio_id' => $portfolio->id,
            'amount' => 1000,
            'frequency' => 'weekly',
            'start_date' => Carbon::now()->toDateString(),
            'next_scheduled_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/sip-plans/{$sipPlan->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('sip_plans', [
            'id' => $sipPlan->id,
        ]);
    }

    public function test_validation_rejects_negative_amount(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson("/api/v1/portfolios/{$portfolio->id}/sip-plans", [
            'amount' => -500,
            'frequency' => 'monthly',
            'start_date' => Carbon::tomorrow()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_validation_rejects_invalid_frequency(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson("/api/v1/portfolios/{$portfolio->id}/sip-plans", [
            'amount' => 500,
            'frequency' => 'hourly',
            'start_date' => Carbon::tomorrow()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['frequency']);
    }

    public function test_next_scheduled_date_is_calculated_correctly_on_create_past_date(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $pastDate = Carbon::now()->subDays(10)->toDateString();

        $response = $this->actingAs($user)->postJson("/api/v1/portfolios/{$portfolio->id}/sip-plans", [
            'amount' => 5000,
            'frequency' => 'weekly',
            'start_date' => $pastDate,
        ]);

        $response->assertStatus(201);

        // Since it's weekly and 10 days ago, it should have missed 1 week (7 days ago), so next should be in 4 days.
        $expectedNextDate = Carbon::parse($pastDate)->addWeeks(2)->toDateString();

        $response->assertJsonPath('data.next_scheduled_date', $expectedNextDate);
    }
    public function test_updating_frequency_recalculates_next_scheduled_date(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $pastDate = Carbon::now()->subDays(10)->toDateString();

        $sipPlan = SipPlan::factory()->create([
            'portfolio_id' => $portfolio->id,
            'amount' => 1000,
            'frequency' => 'weekly',
            'start_date' => $pastDate,
            'next_scheduled_date' => Carbon::parse($pastDate)->addWeeks(2)->toDateString(),
        ]);

        $response = $this->actingAs($user)->putJson("/api/v1/sip-plans/{$sipPlan->id}", [
            'frequency' => 'monthly',
        ]);

        $expectedNextDate = Carbon::parse($pastDate)->addMonthNoOverflow()->toDateString();

        $response->assertStatus(200)
            ->assertJsonPath('data.frequency', 'monthly')
            ->assertJsonPath('data.next_scheduled_date', $expectedNextDate);

        $this->assertDatabaseHas('sip_plans', [
            'id' => $sipPlan->id,
            'frequency' => 'monthly',
            'next_scheduled_date' => Carbon::parse($expectedNextDate)->startOfDay()->toDateTimeString(),
        ]);
    }

    public function test_end_date_validation_rejects_before_start_and_accepts_equal(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $startDate = Carbon::tomorrow()->toDateString();
        $invalidEndDate = Carbon::today()->toDateString();
        $validEndDate = Carbon::tomorrow()->toDateString();

        $responseInvalid = $this->actingAs($user)->postJson("/api/v1/portfolios/{$portfolio->id}/sip-plans", [
            'amount' => 5000,
            'frequency' => 'monthly',
            'start_date' => $startDate,
            'end_date' => $invalidEndDate,
        ]);

        $responseInvalid->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);

        $responseValid = $this->actingAs($user)->postJson("/api/v1/portfolios/{$portfolio->id}/sip-plans", [
            'amount' => 5000,
            'frequency' => 'monthly',
            'start_date' => $startDate,
            'end_date' => $validEndDate,
        ]);

        $responseValid->assertStatus(201)
            ->assertJsonPath('data.end_date', $validEndDate);
    }

    public function test_timezone_boundary_affects_scheduled_date(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        // UTC is 18:00 on Feb 1st
        // New York (UTC-5) is 13:00 on Feb 1st (today)
        // Tokyo (UTC+9) is 03:00 on Feb 2nd (tomorrow)
        Carbon::setTestNow(Carbon::parse('2026-02-01 18:00:00', 'UTC'));

        $startDate = '2026-02-01'; // The date we set as start date

        // For New York, local day is 02-01. Start date is 02-01 (today). Next date = 02-01.
        $responseNy = $this->actingAs($user)->postJson("/api/v1/portfolios/{$portfolio->id}/sip-plans", [
            'amount' => 5000,
            'frequency' => 'daily',
            'start_date' => $startDate,
            'timezone' => 'America/New_York',
        ]);

        $responseNy->assertStatus(201)
            ->assertJsonPath('data.next_scheduled_date', '2026-02-01');

        // For Tokyo, local day is 02-02. Start date is 02-01 (yesterday). Next date = 02-02.
        $responseTokyo = $this->actingAs($user)->postJson("/api/v1/portfolios/{$portfolio->id}/sip-plans", [
            'amount' => 5000,
            'frequency' => 'daily',
            'start_date' => $startDate,
            'timezone' => 'Asia/Tokyo',
        ]);

        $responseTokyo->assertStatus(201)
            ->assertJsonPath('data.next_scheduled_date', '2026-02-02');

        Carbon::setTestNow(null);
    }
}
