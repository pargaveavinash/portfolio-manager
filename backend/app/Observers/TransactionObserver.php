<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\PortfolioCacheService;

class TransactionObserver
{
    private PortfolioCacheService $cacheService;

    public function __construct(PortfolioCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        $this->cacheService->invalidatePortfolioById($transaction->portfolio_id);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        $this->cacheService->invalidatePortfolioById($transaction->portfolio_id);
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        $this->cacheService->invalidatePortfolioById($transaction->portfolio_id);
    }

    /**
     * Handle the Transaction "restored" event.
     */
    public function restored(Transaction $transaction): void
    {
        $this->cacheService->invalidatePortfolioById($transaction->portfolio_id);
    }

    /**
     * Handle the Transaction "forceDeleted" event.
     */
    public function forceDeleted(Transaction $transaction): void
    {
        $this->cacheService->invalidatePortfolioById($transaction->portfolio_id);
    }
}
