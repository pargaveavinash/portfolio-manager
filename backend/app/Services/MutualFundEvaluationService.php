<?php

namespace App\Services;

use App\Domain\Evaluation\EvaluationProfileResolver;
use App\Domain\Evaluation\EvaluationResult;
use App\Domain\Evaluation\MetricResult;
use App\Exceptions\MissingMarketDataException;
use App\Models\MutualFund;
use App\Models\RiskFreeRate;
use App\Domain\RateResolutionPolicy;
use Illuminate\Support\Carbon;

class MutualFundEvaluationService
{
    public function __construct(
        private readonly EvaluationProfileResolver $profileResolver,
        private readonly MutualFundPerformanceService $performanceService,
        private readonly MutualFundAlphaService $alphaService
    ) {
    }

    public function evaluate(
        MutualFund $fund,
        Carbon|string $startDate,
        Carbon|string $endDate,
        ?RiskFreeRate $riskFreeRate = null,
        ?RateResolutionPolicy $policy = null
    ): EvaluationResult
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        $profile = $this->profileResolver->resolve($fund);
        $metrics = [];

        foreach ($profile->getExpectedMetrics() as $metricName) {
            $knownMetrics = ['period_return', 'cagr', 'rolling_returns', 'volatility', 'maximum_drawdown', 'jensens_alpha'];
            
            if (!in_array($metricName, $knownMetrics)) {
                $metrics[] = new MetricResult(
                    name: $metricName,
                    status: MetricResult::STATUS_DATA_UNAVAILABLE,
                    value: null,
                    reason: "Calculation not implemented"
                );
                continue;
            }

            try {
                $value = $this->calculateMetric($metricName, $fund, $start, $end, $riskFreeRate, $policy);
                
                $metrics[] = new MetricResult(
                    name: $metricName,
                    status: MetricResult::STATUS_AVAILABLE,
                    value: $value
                );
            } catch (MissingMarketDataException $e) {
                $metrics[] = new MetricResult(
                    name: $metricName,
                    status: MetricResult::STATUS_INSUFFICIENT_DATA,
                    value: null,
                    reason: $e->getMessage()
                );
            }
        }

        return new EvaluationResult($fund, $profile->getFamily(), $metrics);
    }

    private function calculateMetric(string $metricName, MutualFund $fund, Carbon $start, Carbon $end, ?RiskFreeRate $riskFreeRate = null, ?RateResolutionPolicy $policy = null): float|array
    {
        if ($metricName === 'jensens_alpha') {
            if ($riskFreeRate === null || $policy === null) {
                throw new \InvalidArgumentException("RiskFreeRate and RateResolutionPolicy must be provided to calculate Jensen's Alpha.");
            }
            return $this->alphaService->calculateAlpha($fund, $fund->benchmark, $riskFreeRate, $policy, $start, $end);
        }

        return match ($metricName) {
            'period_return' => $this->performanceService->periodReturn($fund, $start, $end),
            'cagr' => $this->performanceService->cagr($fund, $start, $end),
            'rolling_returns' => $this->calculateRollingReturns($fund, $start, $end),
            'volatility' => $this->performanceService->annualizedVolatility($fund, $start, $end),
            'maximum_drawdown' => $this->performanceService->maximumDrawdown($fund, $start, $end),
            default => throw new \InvalidArgumentException("Unknown metric: {$metricName}")
        };
    }

    private function calculateRollingReturns(MutualFund $fund, Carbon $start, Carbon $end): array
    {
        $results = $this->performanceService->rollingReturns($fund, $start, $end, 12);
        
        if (empty($results)) {
            throw new MissingMarketDataException('Insufficient data for rolling returns window');
        }
        
        return $results;
    }
}
