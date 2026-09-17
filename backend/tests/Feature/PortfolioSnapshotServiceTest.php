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
use App\Services\PortfolioSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PortfolioSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    private PortfolioSnapshotService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PortfolioSnapshotService::class);
    }

    public function test_generate_snapshot_persists_correct_financial_state()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $fund = MutualFund::create([
            'amfi_code' => '123456',
            'scheme_name' => 'Test Fund',
            'amc_name' => 'Test AMC',
            'plan_type' => 'DIRECT',
            'option_type' => 'GROWTH',
            'category' => 'Equity',
            'asset_type' => 'MUTUAL_FUND'
        ]);
        MutualFundNav::create([
            'mutual_fund_id' => $fund->id,
            'nav_date' => '2026-01-01',
            'nav' => 50.00,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol' => $fund->amfi_code,
            'name' => 'Test',
            'asset_type' => 'MUTUAL_FUND',
            'quantity' => 0,
            'average_price' => 0,
            'currency' => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 45.00,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01',
        ]);

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01',
        ]);

        $snapshot = $this->service->generateSnapshot($portfolio, '2026-01-01');

        $this->assertSame('2026-01-01 00:00:00', $snapshot->valuation_date->format('Y-m-d H:i:s'));
        $this->assertSame(450.0, $snapshot->invested_capital); // 10 * 45
        $this->assertSame(500.0, $snapshot->market_value); // 10 * 50
        $this->assertSame(550.0, $snapshot->cash_balance); // 1000 - 450
        $this->assertSame(1050.0, $snapshot->total_value); // 500 + 550
    }

    public function test_generate_snapshot_is_idempotent()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $snapshot1 = $this->service->generateSnapshot($portfolio, '2026-01-01');
        $snapshot2 = $this->service->generateSnapshot($portfolio, '2026-01-01');

        $this->assertSame($snapshot1->id, $snapshot2->id);
        $this->assertSame(1, $portfolio->snapshots()->count());
    }

    public function test_backfill_starts_at_earliest_holding_transaction()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        
        $fund = MutualFund::create([
            'amfi_code' => '654321',
            'scheme_name' => 'Test Fund 2',
            'amc_name' => 'Test AMC',
            'plan_type' => 'DIRECT',
            'option_type' => 'GROWTH',
            'category' => 'Equity',
            'asset_type' => 'MUTUAL_FUND'
        ]);
        MutualFundNav::create([
            'mutual_fund_id' => $fund->id,
            'nav_date' => '2026-01-01',
            'nav' => 50.00,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol' => $fund->amfi_code,
            'name' => 'Test',
            'asset_type' => 'MUTUAL_FUND',
            'quantity' => 0,
            'average_price' => 0,
            'currency' => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 45.00,
            'currency' => 'INR',
            'transaction_date' => '2026-01-03', // Starts here
        ]);

        $this->service->backfillPortfolio($portfolio, '2026-01-05');

        $this->assertSame(3, $portfolio->snapshots()->count());
        $this->assertTrue($portfolio->snapshots()->whereDate('valuation_date', '2026-01-03')->exists());
        $this->assertTrue($portfolio->snapshots()->whereDate('valuation_date', '2026-01-04')->exists());
        $this->assertTrue($portfolio->snapshots()->whereDate('valuation_date', '2026-01-05')->exists());
    }

    public function test_backfill_starts_at_earliest_cash_transaction_if_earlier()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01', // Starts here
        ]);

        $this->service->backfillPortfolio($portfolio, '2026-01-02');

        $this->assertSame(2, $portfolio->snapshots()->count());
        $this->assertTrue($portfolio->snapshots()->whereDate('valuation_date', '2026-01-01')->exists());
        $this->assertTrue($portfolio->snapshots()->whereDate('valuation_date', '2026-01-02')->exists());
    }

    public function test_portfolio_with_no_transactions_generates_no_snapshots()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $this->service->backfillPortfolio($portfolio, '2026-01-05');

        $this->assertSame(0, $portfolio->snapshots()->count());
    }

    public function test_missing_nav_throws_exception_and_halts_backfill_safely()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $fund = MutualFund::create([
            'amfi_code' => '999999',
            'scheme_name' => 'Test Fund 3',
            'amc_name' => 'Test AMC',
            'plan_type' => 'DIRECT',
            'option_type' => 'GROWTH',
            'category' => 'Equity',
            'asset_type' => 'MUTUAL_FUND'
        ]);
        MutualFundNav::create([
            'mutual_fund_id' => $fund->id,
            'nav_date' => '2026-01-01',
            'nav' => 50.00,
        ]);
        // Missing NAVs for days after...

        $holding = $portfolio->holdings()->create([
            'symbol' => $fund->amfi_code,
            'name' => 'Test',
            'asset_type' => 'MUTUAL_FUND',
            'quantity' => 0,
            'average_price' => 0,
            'currency' => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 45.00,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01',
        ]);

        // Attempting to backfill 2025-12-31 will fail missing NAV
        $this->expectException(MissingMarketDataException::class);
        $this->service->generateSnapshot($portfolio, '2025-12-31');
    }

    public function test_cash_only_portfolio_generates_snapshots_with_zero_market_value()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01',
        ]);

        $snapshot = $this->service->generateSnapshot($portfolio, '2026-01-02');

        $this->assertSame(0.0, $snapshot->invested_capital);
        $this->assertSame(0.0, $snapshot->market_value);
        $this->assertSame(1000.0, $snapshot->cash_balance);
        $this->assertSame(1000.0, $snapshot->total_value);
    }
}
