<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\Portfolio;
use App\Models\PortfolioSnapshot;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PortfolioSnapshotService
{
    /**
     * Backfills snapshots for a single portfolio from its earliest transaction up to the given end date.
     *
     * @param Portfolio $portfolio
     * @param string $endDate Date string (YYYY-MM-DD)
     * @return void
     */
    public function backfillPortfolio(Portfolio $portfolio, string $endDate): void
    {
        $startDate = $this->getEarliestTransactionDate($portfolio);

        if (!$startDate) {
            return; // No transactions, nothing to backfill
        }

        $currentDate = Carbon::parse($startDate);
        $finalDate = Carbon::parse($endDate);

        while ($currentDate->lte($finalDate)) {
            $this->generateSnapshot($portfolio, $currentDate->toDateString());
            $currentDate->addDay();
        }
    }

    /**
     * Generates and persists a snapshot for a specific portfolio and date.
     * Uses a DB transaction boundary to ensure atomic snapshot creation.
     *
     * @param Portfolio $portfolio
     * @param string $date Date string (YYYY-MM-DD)
     * @return PortfolioSnapshot
     */
    public function generateSnapshot(Portfolio $portfolio, string $date): PortfolioSnapshot
    {
        // Must parse string to Carbon for historical methods
        $parsedDate = Carbon::parse($date);

        return DB::transaction(function () use ($portfolio, $date, $parsedDate) {
            $investedCapital = $portfolio->historicalInvestedCapital($parsedDate);
            $marketValue = $portfolio->historicalMarketValue($parsedDate);
            $cashBalance = $portfolio->historicalCashBalance($parsedDate);
            $totalValue = $portfolio->historicalTotalValue($parsedDate);

            return $portfolio->snapshots()->updateOrCreate(
                [
                    'valuation_date' => $parsedDate->startOfDay(),
                ],
                [
                    'invested_capital' => $investedCapital,
                    'market_value' => $marketValue,
                    'cash_balance' => $cashBalance,
                    'total_value' => $totalValue,
                ]
            );
        });
    }

    /**
     * Retrieves the earliest transaction date (holding or cash) for a portfolio.
     *
     * @param Portfolio $portfolio
     * @return string|null
     */
    private function getEarliestTransactionDate(Portfolio $portfolio): ?string
    {
        $earliestHoldingTransaction = Transaction::where('portfolio_id', $portfolio->id)
            ->min('transaction_date');

        $earliestCashTransaction = CashTransaction::where('portfolio_id', $portfolio->id)
            ->min('transaction_date');

        if (!$earliestHoldingTransaction && !$earliestCashTransaction) {
            return null;
        }

        if (!$earliestHoldingTransaction) {
            return substr($earliestCashTransaction, 0, 10);
        }

        if (!$earliestCashTransaction) {
            return substr($earliestHoldingTransaction, 0, 10);
        }

        $min = min($earliestHoldingTransaction, $earliestCashTransaction);

        return substr($min, 0, 10);
    }
}
