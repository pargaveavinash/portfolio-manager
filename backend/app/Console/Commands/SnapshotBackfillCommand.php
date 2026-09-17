<?php

namespace App\Console\Commands;

use App\Exceptions\MissingMarketDataException;
use App\Models\Portfolio;
use App\Services\PortfolioSnapshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SnapshotBackfillCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'portfolio:snapshot-backfill {--end-date= : The final date to generate snapshots for (YYYY-MM-DD)} {--portfolio= : Specific portfolio ID to backfill}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfills portfolio snapshots from the earliest transaction up to the given end date';

    /**
     * Execute the console command.
     */
    public function handle(PortfolioSnapshotService $service)
    {
        $endDateStr = $this->option('end-date');

        if ($endDateStr) {
            try {
                $endDate = Carbon::createFromFormat('Y-m-d', $endDateStr)->startOfDay();
            } catch (\Exception $e) {
                $this->error('Invalid date format. Expected YYYY-MM-DD.');
                return 1;
            }
        } else {
            $endDate = Carbon::today();
        }

        $portfolioId = $this->option('portfolio');

        $query = Portfolio::query();
        if ($portfolioId) {
            $query->where('id', $portfolioId);
        }

        $portfolios = $query->get();
        $hasErrors = false;

        $this->info("Starting backfill up to " . $endDate->toDateString());

        foreach ($portfolios as $portfolio) {
            $this->info("Processing Portfolio ID: {$portfolio->id}");

            try {
                $service->backfillPortfolio($portfolio, $endDate->toDateString());
            } catch (MissingMarketDataException $e) {
                $this->error("Failed to generate snapshot for Portfolio ID: {$portfolio->id}. " . $e->getMessage());
                $hasErrors = true;
                continue;
            } catch (\Exception $e) {
                $this->error("Unexpected error processing Portfolio ID: {$portfolio->id}. " . $e->getMessage());
                $hasErrors = true;
                continue;
            }
        }

        $this->info("Backfill complete.");

        return $hasErrors ? 1 : 0;
    }
}
