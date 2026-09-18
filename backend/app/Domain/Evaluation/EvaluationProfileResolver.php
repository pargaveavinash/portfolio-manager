<?php

namespace App\Domain\Evaluation;

use App\Models\MutualFund;
use InvalidArgumentException;

class EvaluationProfileResolver
{
    public function resolve(MutualFund $fund): EvaluationProfile
    {
        if ($fund->sub_category === 'Index Funds/ETFs') {
            return new EvaluationProfile('Passive', [
                'cagr',
                'benchmark_comparison',
                'ter'
            ]);
        }

        if ($fund->category === 'Equity') {
            return new EvaluationProfile('Equity', [
                'period_return',
                'cagr',
                'rolling_returns',
                'volatility',
                'maximum_drawdown'
            ]);
        }

        if ($fund->category === 'Debt') {
            return new EvaluationProfile('Debt', [
                'cagr',
                'volatility',
                'maximum_drawdown'
            ]);
        }

        if ($fund->category === 'Hybrid') {
            return new EvaluationProfile('Hybrid', [
                'cagr',
                'rolling_returns',
                'volatility',
                'maximum_drawdown'
            ]);
        }

        throw new InvalidArgumentException('Unsupported mutual fund category');
    }
}
