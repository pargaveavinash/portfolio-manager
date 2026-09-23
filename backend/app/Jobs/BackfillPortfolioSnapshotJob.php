<?php

namespace App\Jobs;

use App\Services\PortfolioSnapshotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BackfillPortfolioSnapshotJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    
    public int $portfolioId;
    public string $endDate;

    public function __construct(int $portfolioId, string $endDate)
    {
        $this->portfolioId = $portfolioId;
        $this->endDate = $endDate;
    }

    public function backoff(): int
    {
        return 30;
    }

    public function uniqueId(): string
    {
        return (string) $this->portfolioId;
    }

    public function handle(PortfolioSnapshotService $service): void
    {
        $portfolio = \App\Models\Portfolio::find($this->portfolioId);
        if ($portfolio) {
            $service->backfillPortfolio($portfolio, $this->endDate);
        }
    }
}
