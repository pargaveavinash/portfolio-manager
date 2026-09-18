<?php

namespace App\Console\Commands;

use App\Services\MarketData\MutualFundNavSyncService;
use Illuminate\Console\Command;

class SyncMutualFundNavsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mutual-funds:sync-navs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync mutual fund NAVs from the configured provider';

    /**
     * Execute the console command.
     */
    public function handle(MutualFundNavSyncService $syncService)
    {
        $this->info('Starting mutual fund NAV sync...');

        try {
            $syncService->sync();
            $this->info('Mutual fund NAV sync completed successfully.');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Mutual fund NAV sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
