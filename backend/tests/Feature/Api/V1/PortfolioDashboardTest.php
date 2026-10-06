<?php

namespace Tests\Feature\Api\V1;

use App\Models\CashTransaction;
use App\Models\Holding;
use App\Models\MutualFund;
use App\Models\MutualFundNav;
use App\Models\Portfolio;
use App\Models\PortfolioAllocation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Portfolio $portfolio;

    private string $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->portfolio = Portfolio::factory()->create([
            'user_id' => $this->user->id,
            'base_currency' => 'INR',
        ]);
        $this->endpoint = "/api/v1/portfolios/{$this->portfolio->id}/dashboard";
    }

    public function test_unauthenticated_user_cannot_access_dashboard()
    {
        $response = $this->getJson($this->endpoint);
        $response->assertStatus(401);
    }

    public function test_user_cannot_access_another_users_dashboard()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->getJson($this->endpoint);

        $response->assertStatus(404);
    }

    public function test_empty_portfolio_returns_zero_values()
    {
        $response = $this->actingAs($this->user)->getJson($this->endpoint);

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'portfolio' => [
                    'id' => $this->portfolio->id,
                    'name' => $this->portfolio->name,
                    'base_currency' => 'INR',
                ],
                'summary' => [
                    'total_invested_cost' => '0.000000',
                    'current_market_value' => '0.000000',
                    'cash_balance' => '0.000000',
                    'total_portfolio_value' => '0.000000',
                    'unrealized_profit_loss' => '0.000000',
                    'unrealized_profit_loss_percentage' => '0.000000',
                    'realized_profit_loss' => '0.000000',
                    'realized_profit_loss_percentage' => '0.000000',
                    'total_profit_loss' => '0.000000',
                    'total_profit_loss_percentage' => '0.000000',
                ],
                'allocation' => [],
                'holdings' => [],
            ],
        ]);
    }

    public function test_dashboard_calculates_correct_metrics_with_6_decimal_precision()
    {
        // 1. Cash Deposit of 50000
        CashTransaction::forceCreate([
            'portfolio_id' => $this->portfolio->id,
            'type' => 'DEPOSIT',
            'amount' => 50000,
            'currency' => 'INR',
            'transaction_date' => now()->subDays(10)->toDateString(),
        ]);

        // 2. Buy Holding (Equity)
        $equityHolding = Holding::forceCreate([
            'portfolio_id' => $this->portfolio->id,
            'asset_type' => 'EQUITY',
            'symbol' => 'TCS',
            'name' => 'Tata Consultancy Services',
            'currency' => 'INR',
            'quantity' => 10,
            'average_price' => 3000,
            'market_price' => 3500,
        ]);

        Transaction::forceCreate([
            'portfolio_id' => $this->portfolio->id,
            'holding_id' => $equityHolding->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 3000,
            'transaction_date' => now()->subDays(5)->toDateString(),
        ]);

        // Sell 5 units of Equity to realize some profit
        Transaction::forceCreate([
            'portfolio_id' => $this->portfolio->id,
            'holding_id' => $equityHolding->id,
            'type' => 'SELL',
            'quantity' => 5,
            'price' => 3200, // profit of 200 per share on 5 shares = 1000 realized profit
            'transaction_date' => now()->subDays(2)->toDateString(),
        ]);

        // Update holding to reflect current state
        $equityHolding->forceFill([
            'quantity' => 5,
            'average_price' => 3000,
        ])->save();

        // Set allocation target
        PortfolioAllocation::forceCreate([
            'portfolio_id' => $this->portfolio->id,
            'symbol' => 'TCS',
            'target_percentage' => 100.0,
        ]);

        // Action
        $response = $this->actingAs($this->user)->getJson($this->endpoint);

        $response->assertStatus(200);

        // Assertions
        $response->assertJson([
            'data' => [
                'summary' => [
                    'total_invested_cost' => '15000.000000',
                    'current_market_value' => '17500.000000',
                    'cash_balance' => '36000.000000',
                    'total_portfolio_value' => '53500.000000',
                    'unrealized_profit_loss' => '2500.000000',
                    'unrealized_profit_loss_percentage' => '16.666667',
                    'realized_profit_loss' => '1000.000000',
                    'realized_profit_loss_percentage' => '6.666667',
                    'total_profit_loss' => '3500.000000',
                    'total_profit_loss_percentage' => '11.666667',
                ],
                'allocation' => [
                    [
                        'symbol' => 'TCS',
                        'target' => '100.000000',
                        'current' => '100.000000',
                        'deviation' => '0.000000',
                    ],
                ],
                'holdings' => [
                    [
                        'symbol' => 'TCS',
                        'name' => 'Tata Consultancy Services',
                        'asset_type' => 'EQUITY',
                        'quantity' => '5.000000',
                        'average_price' => '3000.000000',
                        'market_price' => '3500.000000',
                        'invested_cost' => '15000.000000',
                        'market_value' => '17500.000000',
                        'unrealized_profit_loss' => '2500.000000',
                        'unrealized_profit_loss_percentage' => '16.666667',
                    ],
                ],
            ],
        ]);

        // Ensure action and amount are NOT in the allocation response
        $allocation = $response->json('data.allocation.0');
        $this->assertArrayNotHasKey('action', $allocation);
        $this->assertArrayNotHasKey('amount', $allocation);
    }

    public function test_missing_market_data_bubbles_up_409_conflict()
    {
        $mutualFund = MutualFund::forceCreate([
            'amfi_code' => '123456',
            'scheme_name' => 'Test Scheme',
            'category' => 'Equity',
            'amc_name' => 'Test AMC',
            'plan_type' => 'Direct',
            'option_type' => 'Growth',
        ]);

        $holding = Holding::forceCreate([
            'portfolio_id' => $this->portfolio->id,
            'asset_type' => 'MUTUAL_FUND',
            'symbol' => '123456',
            'name' => 'Test Fund',
            'currency' => 'INR',
            'quantity' => 10,
            'average_price' => 100,
            'market_price' => 100,
        ]);

        Transaction::forceCreate([
            'portfolio_id' => $this->portfolio->id,
            'holding_id' => $holding->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 100,
            'transaction_date' => now()->toDateString(),
        ]);

        // No MutualFundNav record is created, so getApplicableNav will return null

        $response = $this->actingAs($this->user)->getJson($this->endpoint);

        $response->assertStatus(409);
        $response->assertJsonStructure([
            'message',
            'errors' => ['market_data'],
        ]);
        $this->assertEquals('Market data is temporarily unavailable for one or more holdings.', $response->json('message'));
    }

    public function test_prevents_n_plus_one_queries()
    {
        // Create 2 holdings with 2 transactions each
        $this->seedHoldingsWithTransactions(2);

        // Warm up and record queries for 2 holdings
        DB::enableQueryLog();
        $this->actingAs($this->user)->getJson($this->endpoint)->assertStatus(200);
        $queriesWith2Holdings = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        // Add 10 more holdings
        $this->seedHoldingsWithTransactions(10);

        // Record queries for 12 holdings
        DB::enableQueryLog();
        $this->actingAs($this->user)->getJson($this->endpoint)->assertStatus(200);
        $queriesWith12Holdings = count(DB::getQueryLog());
        DB::disableQueryLog();

        // The number of queries should not grow linearly with the number of holdings
        $this->assertLessThanOrEqual(
            $queriesWith2Holdings + 2,
            $queriesWith12Holdings,
            "N+1 query pattern detected: queries grew from {$queriesWith2Holdings} to {$queriesWith12Holdings} after adding 10 holdings."
        );
    }

    private function seedHoldingsWithTransactions(int $count)
    {
        for ($i = 0; $i < $count; $i++) {
            $holding = Holding::forceCreate([
                'portfolio_id' => $this->portfolio->id,
                'asset_type' => 'EQUITY',
                'symbol' => "SYM{$i}_".uniqid(),
                'name' => "Company {$i}",
                'currency' => 'INR',
                'quantity' => 10,
                'average_price' => 100,
                'market_price' => 150,
            ]);

            Transaction::forceCreate([
                'portfolio_id' => $this->portfolio->id,
                'holding_id' => $holding->id,
                'type' => 'BUY',
                'quantity' => 10,
                'price' => 100,
                'transaction_date' => now()->toDateString(),
            ]);
        }
    }
}
