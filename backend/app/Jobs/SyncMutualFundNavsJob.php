<?php

namespace App\Jobs;

use App\Services\MarketData\MutualFundNavSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncMutualFundNavsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function backoff(): array
    {
        return [60, 180];
    }

    public function handle(MutualFundNavSyncService $service): void
    {
        $service->sync();
    }
}
