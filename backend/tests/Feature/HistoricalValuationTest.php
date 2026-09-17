<?php

namespace Tests\Feature;

use App\Exceptions\MissingMarketDataException;
use App\Models\CashTransaction;
use App\Models\Holding;
use App\Models\MutualFund;
use App\Models\MutualFundNav;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Carbon;

class HistoricalValuationTest extends TestCase
{
    use RefreshDatabase;

    public function test_holding_historical_quantity_includes_only_transactions_up_to_date()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $holding = $portfolio->holdings()->create(['symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'quantity' => 0, 'average_price' => 0, 'currency' => 'INR']);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 100,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01 10:00:00',
        ]);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'SELL',
            'quantity' => 2,
            'price' => 150,
            'currency' => 'INR',
            'transaction_date' => '2026-01-05 10:00:00',
        ]);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 5,
            'price' => 120,
            'currency' => 'INR',
            'transaction_date' => '2026-01-10 10:00:00', // After X
        ]);

        $dateX = Carbon::parse('2026-01-06 23:59:59');

        $this->assertSame(8.0, $holding->historicalQuantity($dateX));
    }

    public function test_holding_historical_average_cost_is_calculated_from_buys_up_to_date()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $holding = $portfolio->holdings()->create(['symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'quantity' => 0, 'average_price' => 0, 'currency' => 'INR']);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 100,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01 10:00:00',
        ]);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'SELL',
            'quantity' => 5,
            'price' => 150,
            'currency' => 'INR',
            'transaction_date' => '2026-01-02 10:00:00',
        ]);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 5,
            'price' => 200,
            'currency' => 'INR',
            'transaction_date' => '2026-01-05 10:00:00',
        ]);
        
        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 300,
            'currency' => 'INR',
            'transaction_date' => '2026-01-10 10:00:00', // After X
        ]);

        $dateX = Carbon::parse('2026-01-06 23:59:59');

        // At 2026-01-01: qty=10, avg=100
        // At 2026-01-02: qty=5, avg=100 (SELL preserves avg)
        // At 2026-01-05: BUY 5 @ 200. Total invested = (5*100) + (5*200) = 1500. Total qty = 10. Avg = 150.

        $this->assertSame(150.0, $holding->historicalAveragePrice($dateX));
        $this->assertSame(1500.0, $holding->historicalInvestedCost($dateX));
    }

    public function test_historical_calculations_handle_zero_quantity_correctly()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $holding = $portfolio->holdings()->create(['symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'quantity' => 0, 'average_price' => 0, 'currency' => 'INR']);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 100,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01 10:00:00',
        ]);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'SELL',
            'quantity' => 10,
            'price' => 150,
            'currency' => 'INR',
            'transaction_date' => '2026-01-05 10:00:00',
        ]);

        $dateX = Carbon::parse('2026-01-06 23:59:59');

        $this->assertSame(0.0, $holding->historicalQuantity($dateX));
        $this->assertSame(0.0, $holding->historicalAveragePrice($dateX));
        $this->assertSame(0.0, $holding->historicalInvestedCost($dateX));
    }

    public function test_multiple_transactions_on_same_date_use_id_ordering()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $holding = $portfolio->holdings()->create(['symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'quantity' => 0, 'average_price' => 0, 'currency' => 'INR']);

        $date = '2026-01-01 10:00:00';

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 100,
            'currency' => 'INR',
            'transaction_date' => $date,
        ]);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 200,
            'currency' => 'INR',
            'transaction_date' => $date,
        ]);

        $dateX = Carbon::parse('2026-01-01 23:59:59');

        // Qty = 20. Total invested = 1000 + 2000 = 3000. Avg = 150.
        $this->assertSame(150.0, $holding->historicalAveragePrice($dateX));
    }

    public function test_soft_deleted_transactions_are_ignored_in_historical_calculation()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $holding = $portfolio->holdings()->create(['symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'quantity' => 0, 'average_price' => 0, 'currency' => 'INR']);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 100,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01 10:00:00',
        ]);

        $deletedTx = $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'SELL',
            'quantity' => 5,
            'price' => 150,
            'currency' => 'INR',
            'transaction_date' => '2026-01-02 10:00:00',
        ]);
        
        $deletedTx->delete(); // Soft delete

        $dateX = Carbon::parse('2026-01-03 23:59:59');

        $this->assertSame(10.0, $holding->historicalQuantity($dateX));
    }

    public function test_regression_historical_market_value_does_not_use_current_quantity()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        
        $mutualFund = MutualFund::create(['symbol' => 'RELIANCE-MF', 'name' => 'Reliance MF', 'amfi_code' => 'RELIANCE-MF', 'amc_name' => 'Reliance AMC', 'scheme_name' => 'Reliance Scheme', 'plan_type' => 'DIRECT', 'option_type' => 'GROWTH']);
        MutualFundNav::create(['mutual_fund_id' => $mutualFund->id, 'nav' => 150, 'nav_date' => '2026-01-05']);
        
        $holding = $portfolio->holdings()->create([
            'symbol' => 'RELIANCE-MF',
            'name' => 'Reliance MF',
            'asset_type' => 'MUTUAL_FUND',
            'quantity' => 0, // Current quantity is 0
            'average_price' => 0,
            'currency' => 'INR'
        ]);

        // BUY 10 units before X
        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 100,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01 10:00:00',
        ]);

        // SELL all 10 units after X
        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'SELL',
            'quantity' => 10,
            'price' => 150,
            'currency' => 'INR',
            'transaction_date' => '2026-01-10 10:00:00',
        ]);

        // Request valuation at X
        $dateX = Carbon::parse('2026-01-06 23:59:59');

        $this->assertSame(10.0, $holding->historicalQuantity($dateX));
        $this->assertSame(1500.0, $holding->historicalMarketValue($dateX)); // 10 units * 150 NAV
    }

    public function test_missing_historical_nav_throws_exception()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $holding = $portfolio->holdings()->create(['symbol' => 'UNKNOWN-MF', 'name' => 'Unknown', 'asset_type' => 'MUTUAL_FUND', 'quantity' => 0, 'average_price' => 0, 'currency' => 'INR']);
        
        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 100,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01 10:00:00',
        ]);

        $dateX = Carbon::parse('2026-01-06 23:59:59');

        $this->expectException(MissingMarketDataException::class);
        $holding->historicalMarketValue($dateX);
    }

    public function test_historical_cash_balance_includes_exact_date_and_ignores_future_and_soft_deleted()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 10000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01 10:00:00',
        ]);

        $portfolio->cashTransactions()->create([
            'type' => 'WITHDRAWAL',
            'amount' => 2000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-05 23:59:59', // Exact date
        ]);

        $deleted = $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 5000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-02 10:00:00',
        ]);
        $deleted->delete();

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-10 10:00:00', // Future
        ]);

        $dateX = Carbon::parse('2026-01-05 23:59:59');

        $this->assertSame(8000.0, $portfolio->historicalCashBalance($dateX));
    }

    public function test_portfolio_historical_values()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        // Cash
        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 5000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01 10:00:00',
        ]);

        // Holding 1
        $mutualFund = MutualFund::create(['symbol' => 'MF1', 'name' => 'MF1', 'amfi_code' => 'MF1', 'amc_name' => 'AMC 1', 'scheme_name' => 'Scheme 1', 'plan_type' => 'DIRECT', 'option_type' => 'GROWTH']);
        MutualFundNav::create(['mutual_fund_id' => $mutualFund->id, 'nav' => 20, 'nav_date' => '2026-01-05']);
        
        $holding1 = $portfolio->holdings()->create(['symbol' => 'MF1', 'name' => 'MF1', 'asset_type' => 'MUTUAL_FUND', 'quantity' => 0, 'average_price' => 0, 'currency' => 'INR']);
        $holding1->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 100, // Cost = 1000
            'price' => 10,
            'currency' => 'INR',
            'transaction_date' => '2026-01-02 10:00:00',
        ]);

        // Holding 2 (Fully sold)
        $mutualFund2 = MutualFund::create(['symbol' => 'MF2', 'name' => 'MF2', 'amfi_code' => 'MF2', 'amc_name' => 'AMC 2', 'scheme_name' => 'Scheme 2', 'plan_type' => 'DIRECT', 'option_type' => 'GROWTH']);
        MutualFundNav::create(['mutual_fund_id' => $mutualFund2->id, 'nav' => 30, 'nav_date' => '2026-01-05']);
        
        $holding2 = $portfolio->holdings()->create(['symbol' => 'MF2', 'name' => 'MF2', 'asset_type' => 'MUTUAL_FUND', 'quantity' => 0, 'average_price' => 0, 'currency' => 'INR']);
        $holding2->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 50,
            'price' => 10,
            'currency' => 'INR',
            'transaction_date' => '2026-01-03 10:00:00',
        ]);
        $holding2->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'SELL',
            'quantity' => 50,
            'price' => 15,
            'currency' => 'INR',
            'transaction_date' => '2026-01-04 10:00:00', // Sold before X
        ]);

        $dateX = Carbon::parse('2026-01-05 23:59:59');

        // Invested Capital: Holding1(100*10 = 1000) + Holding2(0) = 1000
        $this->assertSame(1000.0, $portfolio->historicalInvestedCapital($dateX));

        // Market Value: Holding1(100*20 = 2000) + Holding2(0) = 2000
        $this->assertSame(2000.0, $portfolio->historicalMarketValue($dateX));

        // Cash: 5000 - 1000 (buy) - 500 (buy) + 750 (sell) = 4250
        $this->assertSame(4250.0, $portfolio->historicalCashBalance($dateX));

        // Total Value: 2000 + 4250 = 6250
        $this->assertSame(6250.0, $portfolio->historicalTotalValue($dateX));
    }

    public function test_cash_only_portfolio_produces_market_value_zero_and_total_value_equal_to_cash()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 5000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01 10:00:00',
        ]);

        $dateX = Carbon::parse('2026-01-05 23:59:59');

        $this->assertSame(0.0, $portfolio->historicalInvestedCapital($dateX));
        $this->assertSame(0.0, $portfolio->historicalMarketValue($dateX));
        $this->assertSame(5000.0, $portfolio->historicalCashBalance($dateX));
        $this->assertSame(5000.0, $portfolio->historicalTotalValue($dateX));
    }
}
