<?php

namespace App\Console\Commands;

use App\Models\Portfolio;
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
    public function handle()
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

        $this->info("Starting backfill up to " . $endDate->toDateString());

        foreach ($portfolios as $portfolio) {
            $this->info("Processing Portfolio ID: {$portfolio->id}");
            \App\Jobs\BackfillPortfolioSnapshotJob::dispatch($portfolio->id, $endDate->toDateString());
        }

        $this->info("Backfill complete.");

        return 0;
    }
}
