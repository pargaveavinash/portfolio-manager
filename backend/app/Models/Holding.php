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
        return (float) $this->transactions()
            ->get()
            ->sum(function (Transaction $transaction): float {
                return $transaction->type === 'BUY'
                    ? (float) $transaction->quantity
                    : -(float) $transaction->quantity;
            });
    }

    public function currentAveragePrice(): float
    {
        $quantity  = 0.0;
        $costBasis = 0.0;

        $transactions = $this->transactions()
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

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
        return $this->currentQuantity() * $this->currentAveragePrice();
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
            foreach ($this->transactions()->get() as $transaction) {
                $quantityStr = $transaction->type === 'BUY'
                    ? bcadd($quantityStr, (string) $transaction->quantity, 6)
                    : bcsub($quantityStr, (string) $transaction->quantity, 6);
            }

            // Multiply without premature truncation (6 + 6 = 12 decimal places max)
            $marketValue = bcmul($quantityStr, $nav, 12);

            // Output rounding at the API boundary
            return (float) $marketValue;
        }

        return $this->currentQuantity() * $this->historicalMarketPrice($date);
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

        $transactions = $this->transactions()
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

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

        $transactions = $this->transactions()
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

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

        $transactions = $this->transactions()
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

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
