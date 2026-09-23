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

    public function test_command_dispatches_jobs_for_all_portfolios()
    {
        \Illuminate\Support\Facades\Queue::fake();

        $user = User::factory()->create();
        $portfolio1 = Portfolio::factory()->for($user)->create();
        $portfolio2 = Portfolio::factory()->for($user)->create();

        $this->artisan('portfolio:snapshot-backfill')
            ->assertExitCode(0);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\BackfillPortfolioSnapshotJob::class, function ($job) use ($portfolio1) {
            return $job->portfolioId === $portfolio1->id;
        });
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\BackfillPortfolioSnapshotJob::class, function ($job) use ($portfolio2) {
            return $job->portfolioId === $portfolio2->id;
        });
    }

    public function test_command_accepts_explicit_end_date_and_passes_to_job()
    {
        \Illuminate\Support\Facades\Queue::fake();

        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $this->artisan('portfolio:snapshot-backfill --end-date=2026-01-03')
            ->assertExitCode(0);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\BackfillPortfolioSnapshotJob::class, function ($job) use ($portfolio) {
            return $job->portfolioId === $portfolio->id && $job->endDate === '2026-01-03';
        });
    }

    public function test_command_filters_by_portfolio_id()
    {
        \Illuminate\Support\Facades\Queue::fake();

        $user = User::factory()->create();
        $portfolio1 = Portfolio::factory()->for($user)->create();
        $portfolio2 = Portfolio::factory()->for($user)->create();

        $this->artisan("portfolio:snapshot-backfill --portfolio={$portfolio1->id} --end-date=2026-01-02")
            ->assertExitCode(0);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\BackfillPortfolioSnapshotJob::class, function ($job) use ($portfolio1) {
            return $job->portfolioId === $portfolio1->id;
        });
        \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\BackfillPortfolioSnapshotJob::class, function ($job) use ($portfolio2) {
            return $job->portfolioId === $portfolio2->id;
        });
    }

    public function test_command_requires_valid_date_format()
    {
        \Illuminate\Support\Facades\Queue::fake();

        $this->artisan('portfolio:snapshot-backfill --end-date=invalid-date')
            ->expectsOutputToContain('Invalid date format')
            ->assertExitCode(1);

        \Illuminate\Support\Facades\Queue::assertNothingPushed();
    }
}
