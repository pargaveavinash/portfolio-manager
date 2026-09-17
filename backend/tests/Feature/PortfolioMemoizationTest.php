<?php

namespace Tests\Feature;

use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioMemoizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_portfolio_memoization_is_cleared_on_refresh()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $holding = $portfolio->holdings()->create([
            'symbol' => 'TEST_FUND',
            'name' => 'Test Scheme',
            'asset_type' => 'MUTUAL_FUND',
            'quantity' => 0,
            'average_price' => 0,
            'market_price' => 10.0,
            'currency' => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 100,
            'currency' => 'INR',
            'transaction_date' => now()->toDateString(),
        ]);

        // Calculate a memoized financial value
        $initialCost = $portfolio->currentInvestedCost();
        
        $this->assertEquals(1000.0, $initialCost);

        // Change the underlying transaction/database state directly
        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 5,
            'price' => 100,
            'currency' => 'INR',
            'transaction_date' => now()->toDateString(),
        ]);

        // Refresh/reload the Portfolio
        $portfolio->refresh();

        // Calculate the same financial value again
        $newCost = $portfolio->currentInvestedCost();

        // Verify the new value is returned (should be 1500, not 1000)
        $this->assertEquals(1500.0, $newCost);
    }
}
