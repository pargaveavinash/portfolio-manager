<?php

namespace Tests\Feature;

use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HoldingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_holding(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/v1/portfolios/{$portfolio->id}/holdings",
                [
                    'symbol'        => 'RELIANCE',
                    'name'          => 'Reliance Industries',
                    'asset_type'    => 'stock',
                    'quantity'      => 10,
                    'average_price' => 1450,
                    'currency'      => 'INR',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.holding.symbol',
                'RELIANCE'
            )
            ->assertJsonPath(
                'data.holding.asset_type',
                'stock'
            )
            ->assertJsonPath(
                'data.holding.quantity',
                10
            )
            ->assertJsonPath(
                'data.holding.average_price',
                1450
            );

        $this->assertDatabaseHas('holdings', [
            'portfolio_id' => $portfolio->id,
            'symbol'       => 'RELIANCE',
            'asset_type'   => 'stock',
        ]);
    }

    public function test_authenticated_user_can_list_their_portfolio_holdings()
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()
            ->for($user)
            ->create();

        $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
        ]);

        $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'etf',
            'quantity'      => 25,
            'average_price' => 250,
            'currency'      => 'INR',
        ]);

        $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/holdings")
            ->assertOk()
            ->assertJsonCount(2, 'data.holdings');
    }

    public function testUserCannotListAnotherUsersPortfolioHoldings(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $otherPortfolio = Portfolio::factory()
            ->for($otherUser)
            ->create();

        $otherPortfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
        ]);

        $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$otherPortfolio->id}/holdings")
            ->assertForbidden();
    }

    public function test_authenticated_user_can_view_holding(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 1450,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}/holdings/{$holding->id}")
            ->assertOk()
            ->assertJsonPath('data.holding.symbol', 'RELIANCE')
            ->assertJsonPath('data.holding.quantity', 10);
    }

    public function test_user_cannot_view_another_users_holding(): void
    {
        $owner     = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $owner->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
        ]);

        $this->actingAs($otherUser)
            ->getJson(
                "/api/v1/portfolios/{$portfolio->id}/holdings/{$holding->id}"
            )
            ->assertForbidden();
    }

    public function test_authenticated_user_can_update_holding(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
        ]);

        $this->actingAs($user)
            ->putJson(
                "/api/v1/portfolios/{$portfolio->id}/holdings/{$holding->id}",
                [
                    'quantity'      => 20,
                    'average_price' => 1500,
                ]
            )
            ->assertOk()
            ->assertJsonPath('data.holding.quantity', 20)
            ->assertJsonPath('data.holding.average_price', 1500);
    }

    public function test_user_cannot_update_another_users_holding(): void
    {
        $owner     = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $owner->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
        ]);

        $this->actingAs($otherUser)
            ->putJson(
                "/api/v1/portfolios/{$portfolio->id}/holdings/{$holding->id}",
                [
                    'quantity'      => 100,
                    'average_price' => 1000,
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseHas('holdings', [
            'id'            => $holding->id,
            'quantity'      => 10,
            'average_price' => 1450,
        ]);
    }

    public function test_authenticated_user_can_delete_holding(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
        ]);

        $this->actingAs($user)
            ->deleteJson(
                "/api/v1/portfolios/{$portfolio->id}/holdings/{$holding->id}"
            )
            ->assertNoContent();

        $this->assertSoftDeleted('holdings', [
            'id' => $holding->id,
        ]);
    }

    public function test_authenticated_user_can_view_holding_performance(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'market_price'  => 1600,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 1450,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 2,
            'price'            => 1550,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        $this->actingAs($user)
            ->getJson(
                "/api/v1/portfolios/{$portfolio->id}/holdings/{$holding->id}"
            )
            ->assertOk()
            ->assertJsonPath('data.holding.symbol', 'RELIANCE')
            ->assertJsonPath('data.holding.quantity', 8)
            ->assertJsonPath('data.holding.invested_cost', 11600)
            ->assertJsonPath('data.holding.market_value', 12800)
            ->assertJsonPath('data.holding.unrealized_profit_loss', 1200)
            ->assertJsonPath(
                'data.holding.unrealized_profit_loss_percentage',
                10.344827586206897
            )
            ->assertJsonPath('data.holding.realized_profit_loss', 200);
    }
}