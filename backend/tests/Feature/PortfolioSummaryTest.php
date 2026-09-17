<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_retrieve_the_summary(): void
    {
        $portfolio = Portfolio::factory()->create();

        $response = $this->getJson("/api/v1/portfolios/{$portfolio->id}/summary");

        $response->assertUnauthorized();
    }

    public function test_user_cannot_retrieve_another_users_portfolio_summary(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = $otherUser->portfolios()->create([
            'name'          => 'Private Portfolio',
            'base_currency' => 'INR',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/summary");

        $response->assertNotFound();
    }

    public function test_authenticated_user_can_retrieve_their_portfolio_summary_and_identity_is_expected(): void
    {
        $user = User::factory()->create();

        $portfolio = $user->portfolios()->create([
            'name'          => 'My Summary Portfolio',
            'base_currency' => 'INR',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/summary");

        $response
            ->assertOk()
            ->assertJsonPath('data.portfolio.id', $portfolio->id)
            ->assertJsonPath('data.portfolio.name', 'My Summary Portfolio');
    }

    public function test_summary_calculates_all_values_correctly(): void
    {
        $user = User::factory()->create();

        $portfolio = $user->portfolios()->create([
            'name'          => 'My Portfolio',
            'base_currency' => 'INR',
        ]);

        // Add 1000 cash
        $portfolio->cashTransactions()->create([
            'type'   => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => now(),
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'market_price'  => 1600, // Current market price
            'currency'      => 'INR',
        ]);

        // Buy 10 shares at 1450 = 14500
        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 1450,
            'currency'         => 'INR',
            'transaction_date' => now()->subDays(2),
        ]);

        // Sell 2 shares at 1550 = 3100
        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 2,
            'price'            => 1550,
            'currency'         => 'INR',
            'transaction_date' => now()->subDay(),
        ]);

        // Remaining Quantity: 8
        // Average Price: 1450
        // Current Invested Cost: 8 * 1450 = 11600
        // Current Market Value: 8 * 1600 = 12800
        // Unrealized P/L: 12800 - 11600 = 1200
        // Unrealized P/L %: (1200 / 11600) * 100 = 10.344827586207
        // Realized Cost: 2 * 1450 = 2900
        // Realized Value: 2 * 1550 = 3100
        // Realized P/L: 3100 - 2900 = 200
        // Realized P/L %: (200 / 2900) * 100 = 6.8965517241379
        // Total P/L: 1200 + 200 = 1400
        // Total P/L %: (1400 / (11600 + 2900)) * 100 = (1400 / 14500) * 100 = 9.6551724137931

        // Cash Balance Calculation:
        // Cash deposited: 1000
        // Investment Cash Flow:
        // Bought: -14500
        // Sold: +3100
        // Cash Balance = 1000 - 14500 + 3100 = -10400

        // Total Portfolio Value:
        // Current Market Value (12800) + Cash Balance (-10400) = 2400

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/summary");

        $response
            ->assertOk()
            ->assertJsonPath('data.summary.total_invested_cost', 11600)
            ->assertJsonPath('data.summary.current_market_value', 12800)
            ->assertJsonPath('data.summary.cash_balance', -10400)
            ->assertJsonPath('data.summary.total_portfolio_value', 2400)
            ->assertJsonPath('data.summary.unrealized_profit_loss', 1200)
            ->assertJsonPath('data.summary.realized_profit_loss', 200)
            ->assertJsonPath('data.summary.total_profit_loss', 1400);

        // Use approximate assertions for floats or exact if precise
        $this->assertEquals(10.344827586206897, $response->json('data.summary.unrealized_profit_loss_percentage'));
        $this->assertEquals(6.896551724137931, $response->json('data.summary.realized_profit_loss_percentage'));
        $this->assertEquals(9.655172413793103, $response->json('data.summary.total_profit_loss_percentage'));
    }

    public function test_summary_handles_empty_portfolio_and_zero_values(): void
    {
        $user = User::factory()->create();

        $portfolio = $user->portfolios()->create([
            'name'          => 'Empty Portfolio',
            'base_currency' => 'INR',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/summary");

        $response
            ->assertOk()
            ->assertJsonPath('data.summary.total_invested_cost', 0)
            ->assertJsonPath('data.summary.current_market_value', 0)
            ->assertJsonPath('data.summary.cash_balance', 0)
            ->assertJsonPath('data.summary.total_portfolio_value', 0)
            ->assertJsonPath('data.summary.unrealized_profit_loss', 0)
            ->assertJsonPath('data.summary.unrealized_profit_loss_percentage', 0)
            ->assertJsonPath('data.summary.realized_profit_loss', 0)
            ->assertJsonPath('data.summary.realized_profit_loss_percentage', 0)
            ->assertJsonPath('data.summary.total_profit_loss', 0)
            ->assertJsonPath('data.summary.total_profit_loss_percentage', 0);
    }
    public function test_summary_fails_cleanly_when_nav_is_missing(): void
    {
        $user = User::factory()->create();

        $portfolio = $user->portfolios()->create([
            'name'          => 'Missing NAV Portfolio',
            'base_currency' => 'INR',
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'UNKNOWN_AMFI',
            'name'          => 'Unknown Fund',
            'asset_type'    => 'MUTUAL_FUND',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 100,
            'currency'         => 'INR',
            'transaction_date' => '2023-10-20 10:00:00',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/summary");

        // The Laravel exception handler will catch the MissingMarketDataException and return 409
        $response->assertStatus(409)
                 ->assertJsonPath('message', 'Market data is temporarily unavailable for one or more holdings.');
    }
}
