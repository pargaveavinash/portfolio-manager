<?php

namespace App\Services\SipPlanner\Strategies;

use App\Models\Holding;
use App\Models\Portfolio;
use App\Services\SipPlanner\Contracts\SipAllocationStrategy;
use Illuminate\Support\Collection;

class OptimizationStrategy implements SipAllocationStrategy
{
    public function calculate(Portfolio $portfolio, float $sipAmount): array
    {
        $currentMarketValue = $portfolio->currentMarketValue();
        $postSipPortfolioValue = $currentMarketValue + $sipAmount;

        $allocations = $portfolio->allocationTargets()->orderBy('id')->get();
        $holdings = $portfolio->holdings()->get();

        $deficits = collect();
        $totalPositiveDeficit = 0.0;

        foreach ($allocations as $allocation) {
            $targetPercentage = $allocation->target_percentage;
            $targetAssetValue = $postSipPortfolioValue * ($targetPercentage / 100);

            $currentAssetValue = $holdings
                ->where('symbol', $allocation->symbol)
                ->sum(fn(Holding $holding): float => $holding->currentMarketValue());

            $rawDeficit = $targetAssetValue - $currentAssetValue;
            $positiveDeficit = max($rawDeficit, 0.0);

            $totalPositiveDeficit += $positiveDeficit;

            $deficits->push([
                'symbol' => $allocation->symbol,
                'raw_deficit' => $rawDeficit,
                'positive_deficit' => $positiveDeficit
            ]);
        }

        $suggestions = collect();
        foreach ($deficits as $deficitInfo) {
            $suggestedAmount = 0.0;
            if ($totalPositiveDeficit > 0) {
                $suggestedAmount = ($deficitInfo['positive_deficit'] / $totalPositiveDeficit) * $sipAmount;
            }

            $suggestions->push([
                'symbol' => $deficitInfo['symbol'],
                'raw_deficit' => $deficitInfo['raw_deficit'],
                'unrounded' => $suggestedAmount,
                'rounded' => round($suggestedAmount, 2)
            ]);
        }

        $sumRounded = $suggestions->sum('rounded');
        $remainder = round($sipAmount - $sumRounded, 2);

        if ($remainder !== 0.0) {
            $suggestions = $suggestions->sort(function ($a, $b) use ($remainder) {
                $diffA = round($a['unrounded'] - $a['rounded'], 4);
                $diffB = round($b['unrounded'] - $b['rounded'], 4);

                $adjustedA = $remainder > 0 ? $diffA : -$diffA;
                $adjustedB = $remainder > 0 ? $diffB : -$diffB;

                if ($adjustedA === $adjustedB) {
                    if ($a['raw_deficit'] === $b['raw_deficit']) {
                        return strcmp($a['symbol'], $b['symbol']);
                    }
                    return $b['raw_deficit'] <=> $a['raw_deficit'];
                }

                return $adjustedB <=> $adjustedA;
            })->values();

            $first = $suggestions->first();

            $suggestions = $suggestions->map(function ($item) use ($first, $remainder) {
                if ($item['symbol'] === $first['symbol']) {
                    $item['rounded'] = round($item['rounded'] + $remainder, 2);
                }
                return $item;
            });
        }

        $final = [];
        foreach ($allocations as $allocation) {
            $suggestion = $suggestions->firstWhere('symbol', $allocation->symbol);
            $final[] = [
                'symbol' => $allocation->symbol,
                'suggested_amount' => max(0.0, $suggestion['rounded'])
            ];
        }

        return $final;
    }
}
