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

    public function test_sync_command_dispatches_mutual_fund_nav_sync_job()
    {
        \Illuminate\Support\Facades\Queue::fake();

        $exitCode = Artisan::call('mutual-funds:sync-navs');

        $this->assertEquals(0, $exitCode);
        
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SyncMutualFundNavsJob::class);
    }
}
