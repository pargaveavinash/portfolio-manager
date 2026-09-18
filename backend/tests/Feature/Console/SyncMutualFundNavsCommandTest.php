<?php

namespace Tests\Feature\Console;

use App\Services\MarketData\MutualFundNavSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class SyncMutualFundNavsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_command_invokes_mutual_fund_nav_sync_service()
    {
        $mockService = Mockery::mock(MutualFundNavSyncService::class);
        $mockService->shouldReceive('sync')->once();

        $this->app->instance(MutualFundNavSyncService::class, $mockService);

        $exitCode = Artisan::call('mutual-funds:sync-navs');

        $this->assertEquals(0, $exitCode);
    }
}
