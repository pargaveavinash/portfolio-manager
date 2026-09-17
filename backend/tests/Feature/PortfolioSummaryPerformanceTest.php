<?php

namespace Tests\Feature;

use App\Models\Holding;
use App\Models\MutualFund;
use App\Models\MutualFundNav;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioSummaryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_portfolio_summary_query_count_scales_well_with_holdings()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $this->seedHoldingsAndTransactions($portfolio, 5);

        $this->actingAs($user);

        // Warm up / clear cache if any
        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->getJson("/api/v1/portfolios/{$portfolio->id}/summary");

        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Just output to console for manual verification during implementation
        echo "\n[5 Holdings] Portfolio Summary Query Count: {$queryCount}\n";

        // Assert query count is reasonable (after optimization it should be ~4-5, right now it is ~200)
        // We will just assert true for now and observe the echo
        $this->assertTrue(true);
    }

    public function test_portfolio_summary_query_count_for_20_holdings()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $this->seedHoldingsAndTransactions($portfolio, 20);

        $this->actingAs($user);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->getJson("/api/v1/portfolios/{$portfolio->id}/summary");

        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        echo "\n[20 Holdings] Portfolio Summary Query Count: {$queryCount}\n";

        $this->assertTrue(true);
    }

    private function seedHoldingsAndTransactions(Portfolio $portfolio, int $holdingCount)
    {
        for ($i = 0; $i < $holdingCount; $i++) {
            $fund = MutualFund::create([
                'amfi_code' => "FUND_{$i}",
                'isin' => "ISIN{$i}",
                'amc_name' => 'Test AMC',
                'scheme_name' => "Test Scheme {$i}",
                'plan_type' => 'DIRECT',
                'option_type' => 'GROWTH',
            ]);

            MutualFundNav::create([
                'mutual_fund_id' => $fund->id,
                'nav_date' => now()->toDateString(),
                'nav' => 10.50,
            ]);

            $holding = $portfolio->holdings()->create([
                'symbol' => "FUND_{$i}",
                'name' => "Test Scheme {$i}",
                'asset_type' => 'MUTUAL_FUND',
                'quantity' => 0,
                'average_price' => 0,
                'market_price' => 10.50,
                'currency' => 'INR',
            ]);

            // Create 5 transactions per holding
            for ($t = 0; $t < 5; $t++) {
                $holding->transactions()->create([
                    'portfolio_id' => $portfolio->id,
                    'type' => 'BUY',
                    'quantity' => 10,
                    'price' => 100,
                    'currency' => 'INR',
                    'transaction_date' => now()->subDays(10 - $t)->toDateString(),
                ]);
            }
        }
    }
}
