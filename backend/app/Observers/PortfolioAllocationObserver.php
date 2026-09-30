<?php

namespace App\Observers;

use App\Models\PortfolioAllocation;
use App\Services\PortfolioCacheService;

class PortfolioAllocationObserver
{
    private PortfolioCacheService $cacheService;

    public function __construct(PortfolioCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the PortfolioAllocation "created" event.
     */
    public function created(PortfolioAllocation $allocation): void
    {
        $this->cacheService->invalidatePortfolioById($allocation->portfolio_id);
    }

    /**
     * Handle the PortfolioAllocation "updated" event.
     */
    public function updated(PortfolioAllocation $allocation): void
    {
        $this->cacheService->invalidatePortfolioById($allocation->portfolio_id);
    }

    /**
     * Handle the PortfolioAllocation "deleted" event.
     */
    public function deleted(PortfolioAllocation $allocation): void
    {
        $this->cacheService->invalidatePortfolioById($allocation->portfolio_id);
    }

    /**
     * Handle the PortfolioAllocation "restored" event.
     */
    public function restored(PortfolioAllocation $allocation): void
    {
        $this->cacheService->invalidatePortfolioById($allocation->portfolio_id);
    }

    /**
     * Handle the PortfolioAllocation "force deleted" event.
     */
    public function forceDeleted(PortfolioAllocation $allocation): void
    {
        $this->cacheService->invalidatePortfolioById($allocation->portfolio_id);
    }
}
