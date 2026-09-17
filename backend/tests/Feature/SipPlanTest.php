<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SipPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_request_sip_plan(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $portfolio->allocationTargets()->create(['symbol' => 'AAPL', 'target_percentage' => 60]);
        $portfolio->allocationTargets()->create(['symbol' => 'MSFT', 'target_percentage' => 40]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/sip-plan?amount=1000");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'sip_amount' => 1000.00,
                    'strategy' => 'optimization',
                    'plan' => [
                        ['symbol' => 'AAPL', 'suggested_amount' => 600.00],
                        ['symbol' => 'MSFT', 'suggested_amount' => 400.00],
                    ],
                ]
            ]);
    }

    public function test_unauthenticated_user_is_rejected(): void
    {
        $portfolio = Portfolio::factory()->create();

        $response = $this->getJson("/api/v1/portfolios/{$portfolio->id}/sip-plan?amount=1000");

        $response->assertUnauthorized();
    }

    public function test_user_cannot_access_another_users_portfolio(): void
    {
        $user = User::factory()->create();
        $otherPortfolio = Portfolio::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$otherPortfolio->id}/sip-plan?amount=1000");

        $response->assertNotFound();
    }

    public function test_missing_amount_returns_422(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/sip-plan");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_zero_amount_returns_422(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/sip-plan?amount=0");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_negative_amount_returns_422(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/sip-plan?amount=-100");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_invalid_non_numeric_amount_returns_422(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/sip-plan?amount=abc");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_unsupported_strategy_returns_422(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $portfolio->allocationTargets()->create(['symbol' => 'AAPL', 'target_percentage' => 100]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/sip-plan?amount=100&strategy=deficit_proportional");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['strategy']);
    }

    public function test_explicit_strategy_optimization_works(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $portfolio->allocationTargets()->create(['symbol' => 'AAPL', 'target_percentage' => 100]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/sip-plan?amount=100&strategy=optimization");

        $response->assertOk()
            ->assertJsonPath('data.strategy', 'optimization');
    }

    public function test_missing_allocation_targets_returns_422(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        // No allocation targets created

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/sip-plan?amount=1000");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Portfolio does not have a valid 100% allocation.');
    }

    public function test_allocation_total_not_equal_to_100_returns_422(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $portfolio->allocationTargets()->create(['symbol' => 'AAPL', 'target_percentage' => 90]);
        // Total is only 90

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/sip-plan?amount=1000");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Portfolio does not have a valid 100% allocation.');
    }

    public function test_sip_planning_does_not_modify_cash_or_create_transactions(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $portfolio->allocationTargets()->create(['symbol' => 'AAPL', 'target_percentage' => 100]);

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 5000,
            'currency' => 'USD',
            'transaction_date' => now()
        ]);

        $this->assertEquals(5000, $portfolio->cashBalance());
        $this->assertCount(0, $portfolio->transactions);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/sip-plan?amount=1000");

        $response->assertOk();

        // Verify database is unchanged
        $this->assertEquals(5000, $portfolio->fresh()->cashBalance());
        $this->assertCount(0, $portfolio->fresh()->transactions);
    }
}
