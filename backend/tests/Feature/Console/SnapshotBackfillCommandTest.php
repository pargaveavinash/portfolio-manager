<?php

namespace Tests\Feature\Console;

use App\Exceptions\MissingMarketDataException;
use App\Models\CashTransaction;
use App\Models\Holding;
use App\Models\MutualFund;
use App\Models\MutualFundNav;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SnapshotBackfillCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_defaults_to_today_if_no_date_provided()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        
        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => Carbon::today()->subDays(2)->toDateString(),
        ]);

        $this->artisan('portfolio:snapshot-backfill')
            ->assertExitCode(0);

        $this->assertSame(3, $portfolio->snapshots()->count());
        $this->assertTrue($portfolio->snapshots()->whereDate('valuation_date', Carbon::today()->toDateString())->exists());
    }

    public function test_command_accepts_explicit_end_date()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        
        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01',
        ]);

        $this->artisan('portfolio:snapshot-backfill --end-date=2026-01-03')
            ->assertExitCode(0);

        $this->assertSame(3, $portfolio->snapshots()->count());
        $this->assertTrue($portfolio->snapshots()->whereDate('valuation_date', '2026-01-03')->exists());
        $this->assertFalse($portfolio->snapshots()->whereDate('valuation_date', '2026-01-04')->exists());
    }

    public function test_command_filters_by_portfolio_id()
    {
        $user = User::factory()->create();
        $portfolio1 = Portfolio::factory()->for($user)->create();
        $portfolio2 = Portfolio::factory()->for($user)->create();
        
        $portfolio1->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01',
        ]);

        $portfolio2->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 2000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01',
        ]);

        $this->artisan("portfolio:snapshot-backfill --portfolio={$portfolio1->id} --end-date=2026-01-02")
            ->assertExitCode(0);

        $this->assertSame(2, $portfolio1->snapshots()->count());
        $this->assertSame(0, $portfolio2->snapshots()->count());
    }

    public function test_command_halts_affected_portfolio_on_missing_nav_but_continues_others()
    {
        $user = User::factory()->create();
        $portfolio1 = Portfolio::factory()->for($user)->create(); // Will fail due to missing NAV
        $portfolio2 = Portfolio::factory()->for($user)->create(); // Will succeed (cash only)

        $fund = MutualFund::create([
            'amfi_code' => '555555',
            'scheme_name' => 'Test',
            'amc_name' => 'Test AMC',
            'plan_type' => 'DIRECT',
            'option_type' => 'GROWTH',
            'category' => 'Equity',
            'asset_type' => 'MUTUAL_FUND'
        ]);
        MutualFundNav::create([
            'mutual_fund_id' => $fund->id,
            'nav_date' => '2026-01-02', // NAV only exists from 02
            'nav' => 50.00,
        ]);

        $holding = $portfolio1->holdings()->create([
            'symbol' => $fund->amfi_code,
            'name' => 'Test',
            'asset_type' => 'MUTUAL_FUND',
            'quantity' => 0,
            'average_price' => 0,
            'currency' => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id' => $portfolio1->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 45.00,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01', // Transaction on 01 where NAV is missing
        ]);

        $portfolio2->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01',
        ]);

        // Portfolio 1 processes first, throws exception on 2026-01-01.
        // Command should catch, error out for Portfolio 1, and continue to Portfolio 2.
        
        $this->artisan('portfolio:snapshot-backfill --end-date=2026-01-02')
            ->expectsOutputToContain('Failed to generate snapshot for Portfolio ID: ' . $portfolio1->id)
            ->assertExitCode(1); // Command can return 1 if there were any errors overall, but still process portfolio2

        // Portfolio 1 should have NO snapshots because it failed on day 1
        $this->assertSame(0, $portfolio1->snapshots()->count());

        // Portfolio 2 should be fully backfilled
        $this->assertSame(2, $portfolio2->snapshots()->count());
    }

    public function test_command_requires_valid_date_format()
    {
        $this->artisan('portfolio:snapshot-backfill --end-date=invalid-date')
            ->expectsOutputToContain('Invalid date format')
            ->assertExitCode(1);
    }
}
