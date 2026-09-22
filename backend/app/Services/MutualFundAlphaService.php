<?php

namespace App\Services;

use App\Models\MutualFund;
use App\Models\Benchmark;
use App\Models\RiskFreeRate;
use App\Domain\RateResolutionPolicy;
use Carbon\Carbon;

class MutualFundAlphaService
{
    private MutualFundPerformanceService $performanceService;
    private MutualFundBenchmarkService $benchmarkService;
    private RiskFreeRateService $riskFreeRateService;

    public function __construct(
        MutualFundPerformanceService $performanceService,
        MutualFundBenchmarkService $benchmarkService,
        RiskFreeRateService $riskFreeRateService
    ) {
        $this->performanceService = $performanceService;
        $this->benchmarkService = $benchmarkService;
        $this->riskFreeRateService = $riskFreeRateService;
    }

    public function calculateAlpha(
        MutualFund $fund,
        Benchmark $benchmark,
        RiskFreeRate $riskFreeRate,
        RateResolutionPolicy $policy,
        Carbon|string $startDate,
        Carbon|string $endDate
    ): float {
        $fundCagr = $this->performanceService->cagr(
            $fund,
            $startDate,
            $endDate
        );

        $benchmarkCagr = $this->benchmarkService->cagr(
            $benchmark,
            $startDate,
            $endDate
        );

        $riskFreeCagr = $this->riskFreeRateService->cagr(
            $riskFreeRate,
            $startDate,
            $endDate,
            $policy,
            RiskFreeRateService::CONVENTION_EFFECTIVE_ANNUAL_365
        );

        $beta = $this->benchmarkService->beta(
            $fund,
            $benchmark,
            $startDate,
            $endDate
        );

        $expectedReturn =
            $riskFreeCagr +
            $beta * ($benchmarkCagr - $riskFreeCagr);

        return $fundCagr - $expectedReturn;
    }
}
