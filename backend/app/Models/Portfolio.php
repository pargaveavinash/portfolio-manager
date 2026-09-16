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

    public function allocationTargets(): HasMany
    {
        return $this->hasMany(PortfolioAllocation::class);
    }

    public function cashTransactions()
    {
        return $this->hasMany(CashTransaction::class);
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
        $realizedCost = $this->realizedCost();

        if ($realizedCost <= 0) {
            return 0.0;
        }

        return ($this->realizedProfitLoss() / $realizedCost) * 100;
    }

    public function totalProfitLoss(): float
    {
        return $this->realizedProfitLoss()
            + $this->unrealizedProfitLoss();
    }

    public function totalProfitLossPercentage(): float
    {
        $costBasis = $this->realizedCost()
            + $this->currentInvestedCost();

        if ($costBasis <= 0) {
            return 0.0;
        }

        return ($this->totalProfitLoss() / $costBasis) * 100;
    }

    public function realizedCost(): float
    {
        return (float) $this->holdings()
            ->get()
            ->sum(
                fn(Holding $holding): float =>
                $holding->realizedCost()
            );
    }

    public function allocationPercentage(): float
    {
        return (float) $this->allocationTargets()
            ->sum('target_percentage');
    }

    public function allocationPercentageTotal(): float
    {
        return (float) $this->allocationTargets()
            ->sum('target_percentage');
    }

    public function hasValidAllocation(): bool
    {
        return $this->allocationPercentageTotal() === 100.0;
    }

    public function currentAllocationPercentage(string $symbol): float
    {
        $holdings = $this->holdings()->get();

        $totalMarketValue = $holdings->sum(
            fn(Holding $holding): float => $holding->currentMarketValue()
        );

        if ($totalMarketValue <= 0) {
            return 0.0;
        }

        $holding = $holdings->firstWhere('symbol', $symbol);

        if (!$holding) {
            return 0.0;
        }

        return ($holding->currentMarketValue() / $totalMarketValue) * 100;
    }

    public function allocationDeviation(string $symbol): float
    {
        $target = $this->allocationTargets()
            ->where('symbol', $symbol)
            ->first();

        if (!$target) {
            return 0.0;
        }

        return $this->currentAllocationPercentage($symbol)
            - (float) $target->target_percentage;
    }

    public function rebalancingAction(string $symbol): string
    {
        $deviation = $this->allocationDeviation($symbol);

        $tolerance = 5.0;

        if (abs($deviation) <= $tolerance + 0.000001) {
            return 'HOLD';
        }

        if ($deviation < 0) {
            return 'BUY';
        }

        return 'SELL';
    }

    public function rebalancingAmount(string $symbol): float
    {
        $target = $this->allocationTargets()
            ->where('symbol', $symbol)
            ->first();

        if (!$target) {
            return 0.0;
        }

        $holdings = $this->holdings()->get();

        $totalMarketValue = $holdings->sum(
            fn(Holding $holding): float => $holding->currentMarketValue()
        );

        if ($totalMarketValue <= 0) {
            return 0.0;
        }

        $holding = $holdings->firstWhere('symbol', $symbol);

        $currentMarketValue = $holding
            ? $holding->currentMarketValue()
            : 0.0;

        $targetMarketValue =
            $totalMarketValue * ((float) $target->target_percentage / 100);

        return abs($targetMarketValue - $currentMarketValue);
    }

    public function rebalancingPlan(): array
    {
        return $this->allocationTargets()
            ->orderBy('id')
            ->get()
            ->map(function (PortfolioAllocation $target): array {
                $symbol = $target->symbol;

                return [
                    'symbol'    => $symbol,
                    'target'    => (float) $target->target_percentage,
                    'current'   => $this->currentAllocationPercentage($symbol),
                    'deviation' => $this->allocationDeviation($symbol),
                    'action'    => $this->rebalancingAction($symbol),
                    'amount'    => $this->rebalancingAmount($symbol),
                ];
            })
            ->values()
            ->all();
    }

    public function cashBalance(): float
    {
        $cashBalance = (float) $this->cashTransactions()
            ->get()
            ->sum(function (CashTransaction $transaction): float {
                return $transaction->type === 'DEPOSIT'
                    ? (float) $transaction->amount
                    : -(float) $transaction->amount;
            });

        $investmentCashFlow = (float) $this->holdings()
            ->with('transactions')
            ->get()
            ->sum(function (Holding $holding): float {
                return $holding->transactions
                    ->sum(function (Transaction $transaction): float {
                        $value = (float) $transaction->quantity
                            * (float) $transaction->price;

                        return $transaction->type === 'BUY'
                            ? -$value
                            : $value;
                    });
            });

        return $cashBalance + $investmentCashFlow;
    }

    public function totalPortfolioValue(): float
    {
        return $this->currentMarketValue() + $this->cashBalance();
    }
}
