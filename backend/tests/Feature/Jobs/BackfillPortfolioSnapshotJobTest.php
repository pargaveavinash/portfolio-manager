<?php

namespace Tests\Feature\Jobs;

use App\Exceptions\MissingMarketDataException;
use App\Jobs\BackfillPortfolioSnapshotJob;
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
use Mockery;

class BackfillPortfolioSnapshotJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_accepts_portfolio_id_and_end_date_and_invokes_service()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        
        $mockService = Mockery::mock(PortfolioSnapshotService::class);
        $mockService->shouldReceive('backfillPortfolio')
            ->once()
            ->withArgs(function ($p, $date) use ($portfolio) {
                return $p->id === $portfolio->id && $date === '2026-01-01';
            });

        $this->app->instance(PortfolioSnapshotService::class, $mockService);

        $job = new BackfillPortfolioSnapshotJob($portfolio->id, '2026-01-01');
        $job->handle($mockService);
    }

    public function test_job_maintains_existing_snapshot_behavior()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        
        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => '2026-01-01',
        ]);

        $service = app(PortfolioSnapshotService::class);
        $job = new BackfillPortfolioSnapshotJob($portfolio->id, '2026-01-03');
        $job->handle($service);

        $this->assertSame(3, $portfolio->snapshots()->count());
        $this->assertTrue($portfolio->snapshots()->whereDate('valuation_date', '2026-01-03')->exists());
    }

    public function test_job_fails_and_halts_on_missing_nav()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create(); 

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
            'nav_date' => '2026-01-02', 
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

        $service = app(PortfolioSnapshotService::class);
        $job = new BackfillPortfolioSnapshotJob($portfolio->id, '2026-01-02');
        
        $this->expectException(\Exception::class); // It will throw MissingMarketDataException or similar
        $job->handle($service);
    }

    public function test_job_has_approved_operational_policies_and_unique_behavior()
    {
        $job = new BackfillPortfolioSnapshotJob(1, '2026-01-01');

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(120, $job->timeout);
        $this->assertEquals(30, $job->backoff());
        $this->assertEquals('1', $job->uniqueId());
    }
}
