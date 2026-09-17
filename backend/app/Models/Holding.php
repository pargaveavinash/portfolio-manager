<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'symbol',
    'name',
    'asset_type',
    'quantity',
    'average_price',
    'market_price',
    'currency',
])]
#[Hidden([
    'portfolio_id',
    'deleted_at',
])]
class Holding extends Model
{
    use HasFactory, SoftDeletes;

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function currentQuantity(): float
    {
        return $this->historicalQuantity(null);
    }

    public function historicalQuantity(?\Illuminate\Support\Carbon $date = null): float
    {
        $transactions = $this->transactions;
        if ($date) {
            $dateString = $date->toDateString();
            $transactions = $transactions->filter(function ($transaction) use ($dateString) {
                $txDate = $transaction->transaction_date instanceof \Illuminate\Support\Carbon 
                    ? $transaction->transaction_date->toDateString() 
                    : substr((string)$transaction->transaction_date, 0, 10);
                return $txDate <= $dateString;
            });
        }
        
        return (float) $transactions
            ->sum(function (Transaction $transaction): float {
                return $transaction->type === 'BUY'
                    ? (float) $transaction->quantity
                    : -(float) $transaction->quantity;
            });
    }

    public function currentAveragePrice(): float
    {
        return $this->historicalAveragePrice(null);
    }

    public function historicalAveragePrice(?\Illuminate\Support\Carbon $date = null): float
    {
        $quantity  = 0.0;
        $costBasis = 0.0;

        $transactions = $this->transactions;
        if ($date) {
            $dateString = $date->toDateString();
            $transactions = $transactions->filter(function ($transaction) use ($dateString) {
                $txDate = $transaction->transaction_date instanceof \Illuminate\Support\Carbon 
                    ? $transaction->transaction_date->toDateString() 
                    : substr((string)$transaction->transaction_date, 0, 10);
                return $txDate <= $dateString;
            });
        }

        $transactions = $transactions->sortBy([
            ['transaction_date', 'asc'],
            ['id', 'asc'],
        ]);

        foreach ($transactions as $transaction) {
            $transactionQuantity = (float) $transaction->quantity;
            $transactionPrice    = (float) $transaction->price;

            if ($transaction->type === 'BUY') {
                $costBasis += $transactionQuantity * $transactionPrice;
                $quantity  += $transactionQuantity;

                continue;
            }

            if ($transaction->type === 'SELL') {
                if ($quantity <= 0) {
                    continue;
                }

                $averageCost = $costBasis / $quantity;

                $costBasis -= $transactionQuantity * $averageCost;
                $quantity  -= $transactionQuantity;
            }
        }

        if ($quantity <= 0) {
            return 0.0;
        }

        return $costBasis / $quantity;
    }

    public function currentInvestedCost(): float
    {
        return $this->historicalInvestedCost(null);
    }

    public function historicalInvestedCost(?\Illuminate\Support\Carbon $date = null): float
    {
        return $this->historicalQuantity($date) * $this->historicalAveragePrice($date);
    }

    public function currentMarketPrice(): float
    {
        return $this->historicalMarketPrice(null);
    }

    public function historicalMarketPrice(?\Illuminate\Support\Carbon $date = null): float
    {
        if ($this->asset_type === 'MUTUAL_FUND') {
            /** @var \App\Services\MarketData\MarketDataValuationService $valuationService */
            $valuationService = app(\App\Services\MarketData\MarketDataValuationService::class);
            $nav = $valuationService->getApplicableNav(
                $this->symbol,
                $this->asset_type,
                $date ? $date->toDateString() : null
            );

            if ($nav === null) {
                throw new \App\Exceptions\MissingMarketDataException("NAV not found for mutual fund: {$this->symbol}");
            }

            // We return float at the final API boundary for non-bcmath consumers
            return (float) $nav;
        }

        return (float) $this->market_price;
    }

    public function currentMarketValue(): float
    {
        return $this->historicalMarketValue(null);
    }

    public function historicalMarketValue(?\Illuminate\Support\Carbon $date = null): float
    {
        if ($this->asset_type === 'MUTUAL_FUND') {
            /** @var \App\Services\MarketData\MarketDataValuationService $valuationService */
            $valuationService = app(\App\Services\MarketData\MarketDataValuationService::class);
            $nav = $valuationService->getApplicableNav(
                $this->symbol,
                $this->asset_type,
                $date ? $date->toDateString() : null
            );

            if ($nav === null) {
                throw new \App\Exceptions\MissingMarketDataException("NAV not found for mutual fund: {$this->symbol}");
            }

            // Calculate exact quantity string to avoid float precision loss during aggregation
            $quantityStr = '0';
            $transactions = $this->transactions;
            if ($date) {
                $dateString = $date->toDateString();
                $transactions = $transactions->filter(function ($transaction) use ($dateString) {
                    $txDate = $transaction->transaction_date instanceof \Illuminate\Support\Carbon 
                        ? $transaction->transaction_date->toDateString() 
                        : substr((string)$transaction->transaction_date, 0, 10);
                    return $txDate <= $dateString;
                });
            }

            foreach ($transactions as $transaction) {
                $quantityStr = $transaction->type === 'BUY'
                    ? bcadd($quantityStr, (string) $transaction->quantity, 6)
                    : bcsub($quantityStr, (string) $transaction->quantity, 6);
            }

            // Multiply without premature truncation (6 + 6 = 12 decimal places max)
            $marketValue = bcmul($quantityStr, $nav, 12);

            // Output rounding at the API boundary
            return (float) $marketValue;
        }

        return $this->historicalQuantity($date) * $this->historicalMarketPrice($date);
    }

    public function unrealizedProfitLoss(): float
    {
        return $this->currentMarketValue() - $this->currentInvestedCost();
    }

    public function unrealizedProfitLossPercentage(): float
    {
        $investedCost = $this->currentInvestedCost();

        if ($investedCost <= 0) {
            return 0.0;
        }

        return ($this->unrealizedProfitLoss() / $investedCost) * 100;
    }

    public function realizedProfitLoss(): float
    {
        $quantity           = 0.0;
        $costBasis          = 0.0;
        $realizedProfitLoss = 0.0;

        $transactions = $this->transactions->sortBy([
            ['transaction_date', 'asc'],
            ['id', 'asc'],
        ]);

        foreach ($transactions as $transaction) {
            $transactionQuantity = (float) $transaction->quantity;
            $transactionPrice    = (float) $transaction->price;

            if ($transaction->type === 'BUY') {
                $quantity  += $transactionQuantity;
                $costBasis += $transactionQuantity * $transactionPrice;

                continue;
            }

            if ($transaction->type === 'SELL') {
                if ($quantity <= 0 || $transactionQuantity <= 0) {
                    continue;
                }

                $averageCost = $costBasis / $quantity;

                $realizedProfitLoss +=
                    $transactionQuantity * ($transactionPrice - $averageCost);

                $costBasis -= $transactionQuantity * $averageCost;
                $quantity  -= $transactionQuantity;
            }
        }

        return $realizedProfitLoss;
    }

    public function realizedProfitLossPercentage(): float
    {
        $quantity           = 0.0;
        $costBasis          = 0.0;
        $realizedProfitLoss = 0.0;
        $realizedCost       = 0.0;

        $transactions = $this->transactions->sortBy([
            ['transaction_date', 'asc'],
            ['id', 'asc'],
        ]);

        foreach ($transactions as $transaction) {
            $transactionQuantity = (float) $transaction->quantity;
            $transactionPrice    = (float) $transaction->price;

            if ($transaction->type === 'BUY') {
                $quantity  += $transactionQuantity;
                $costBasis += $transactionQuantity * $transactionPrice;

                continue;
            }

            if ($transaction->type === 'SELL') {
                if ($quantity <= 0 || $transactionQuantity <= 0) {
                    continue;
                }

                $averageCost = $costBasis / $quantity;

                $realizedCost += $transactionQuantity * $averageCost;

                $realizedProfitLoss +=
                    $transactionQuantity * ($transactionPrice - $averageCost);

                $costBasis -= $transactionQuantity * $averageCost;
                $quantity  -= $transactionQuantity;
            }
        }

        if ($realizedCost <= 0) {
            return 0.0;
        }

        return ($realizedProfitLoss / $realizedCost) * 100;
    }

    public function realizedCost(): float
    {
        $quantity     = 0.0;
        $costBasis    = 0.0;
        $realizedCost = 0.0;

        $transactions = $this->transactions->sortBy([
            ['transaction_date', 'asc'],
            ['id', 'asc'],
        ]);

        foreach ($transactions as $transaction) {
            $transactionQuantity = (float) $transaction->quantity;
            $transactionPrice    = (float) $transaction->price;

            if ($transaction->type === 'BUY') {
                $quantity  += $transactionQuantity;
                $costBasis += $transactionQuantity * $transactionPrice;
                continue;
            }

            if ($transaction->type === 'SELL') {
                if ($quantity <= 0 || $transactionQuantity <= 0) {
                    continue;
                }

                $averageCost = $costBasis / $quantity;

                $realizedCost += $transactionQuantity * $averageCost;

                $costBasis -= $transactionQuantity * $averageCost;
                $quantity  -= $transactionQuantity;
            }
        }

        return $realizedCost;
    }
}
