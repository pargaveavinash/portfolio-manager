<?php

namespace App\Observers;

use App\Models\Holding;
use App\Services\PortfolioCacheService;

class HoldingObserver
{
    private PortfolioCacheService $cacheService;

    public function __construct(PortfolioCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Holding "created" event.
     */
    public function created(Holding $holding): void
    {
        $this->cacheService->invalidatePortfolioById($holding->portfolio_id);
    }

    /**
     * Handle the Holding "updated" event.
     */
    public function updated(Holding $holding): void
    {
        $this->cacheService->invalidatePortfolioById($holding->portfolio_id);
    }

    /**
     * Handle the Holding "deleted" event.
     */
    public function deleted(Holding $holding): void
    {
        $this->cacheService->invalidatePortfolioById($holding->portfolio_id);
    }

    /**
     * Handle the Holding "restored" event.
     */
    public function restored(Holding $holding): void
    {
        $this->cacheService->invalidatePortfolioById($holding->portfolio_id);
    }

    /**
     * Handle the Holding "forceDeleted" event.
     */
    public function forceDeleted(Holding $holding): void
    {
        $this->cacheService->invalidatePortfolioById($holding->portfolio_id);
    }
}
