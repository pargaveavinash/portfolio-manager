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
        return (float) $this->market_price;
    }

    public function currentMarketValue(): float
    {
        return $this->currentQuantity() * $this->currentMarketPrice();
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
}
