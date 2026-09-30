<?php

namespace App\Observers;

use App\Models\CashTransaction;
use App\Services\PortfolioCacheService;

class CashTransactionObserver
{
    private PortfolioCacheService $cacheService;

    public function __construct(PortfolioCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the CashTransaction "created" event.
     */
    public function created(CashTransaction $transaction): void
    {
        $this->cacheService->invalidatePortfolioById($transaction->portfolio_id);
    }

    /**
     * Handle the CashTransaction "updated" event.
     */
    public function updated(CashTransaction $transaction): void
    {
        $this->cacheService->invalidatePortfolioById($transaction->portfolio_id);
    }

    /**
     * Handle the CashTransaction "deleted" event.
     */
    public function deleted(CashTransaction $transaction): void
    {
        $this->cacheService->invalidatePortfolioById($transaction->portfolio_id);
    }

    /**
     * Handle the CashTransaction "restored" event.
     */
    public function restored(CashTransaction $transaction): void
    {
        $this->cacheService->invalidatePortfolioById($transaction->portfolio_id);
    }

    /**
     * Handle the CashTransaction "forceDeleted" event.
     */
    public function forceDeleted(CashTransaction $transaction): void
    {
        $this->cacheService->invalidatePortfolioById($transaction->portfolio_id);
    }
}
