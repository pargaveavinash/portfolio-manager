<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SyncMutualFundNavsJob;
use App\Services\MarketData\MutualFundNavSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncMutualFundNavsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_invokes_mutual_fund_nav_sync_service()
    {
        $mockService = Mockery::mock(MutualFundNavSyncService::class);
        $mockService->shouldReceive('sync')->once();

        $this->app->instance(MutualFundNavSyncService::class, $mockService);

        $job = new SyncMutualFundNavsJob();
        $job->handle($mockService);
    }

    public function test_job_has_approved_operational_policies()
    {
        $job = new SyncMutualFundNavsJob();

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(300, $job->timeout);
        $this->assertEquals([60, 180], $job->backoff());
    }
}
