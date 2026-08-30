<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'base_currency'])]
class Portfolio extends Model
{
    use HasFactory, SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(Holding::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function currentInvestedCost(): float
    {
        return (float) $this->holdings()
            ->get()
            ->sum(
                fn(Holding $holding): float =>
                $holding->currentInvestedCost()
            );
    }

    public function currentMarketValue(): float
    {
        return (float) $this->holdings()
            ->get()
            ->sum(
                fn(Holding $holding): float =>
                $holding->currentMarketValue()
            );
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
        return (float) $this->holdings()
            ->get()
            ->sum(
                fn(Holding $holding): float =>
                $holding->realizedProfitLoss()
            );
    }

    public function realizedProfitLossPercentage(): float
    {
        $investedCost = $this->currentInvestedCost();

        if ($investedCost <= 0) {
            return 0.0;
        }

        return ($this->realizedProfitLoss() / $investedCost) * 100;
    }

    public function totalProfitLoss(): float
    {
        return $this->realizedProfitLoss()
            + $this->unrealizedProfitLoss();
    }

    public function totalProfitLossPercentage(): float
    {
        $investedCost = $this->currentInvestedCost();

        if ($investedCost <= 0) {
            return 0.0;
        }

        return ($this->totalProfitLoss() / $investedCost) * 100;
    }
}
