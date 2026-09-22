<?php

namespace App\Services;

use App\Exceptions\MissingMarketDataException;
use App\Models\Benchmark;
use App\Models\BenchmarkValue;
use App\Models\MutualFund;
use App\Models\MutualFundNav;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Throwable;

class MutualFundBenchmarkService
{
    public function resolveValue(Benchmark $benchmark, Carbon|string $valuationDate): float
    {
        $date = $this->parseDate($valuationDate);

        $benchmarkValue = BenchmarkValue::where('benchmark_id', $benchmark->id)
            ->where('valuation_date', '<=', $date->copy()->endOfDay())
            ->orderBy('valuation_date', 'desc')
            ->first();

        if (!$benchmarkValue) {
            throw new MissingMarketDataException();
        }

        return (float) $benchmarkValue->value;
    }

    public function periodReturn(Benchmark $benchmark, Carbon|string $startDate, Carbon|string $endDate): float
    {
        $start = $this->parseDate($startDate);
        $end = $this->parseDate($endDate);

        if ($start->greaterThan($end)) {
            throw new InvalidArgumentException("Start date cannot be after end date.");
        }

        $startValue = $this->resolveValue($benchmark, $start);

        if ($startValue === 0.0) {
            throw new InvalidArgumentException("Starting benchmark value is zero.");
        }

        $endValue = $this->resolveValue($benchmark, $end);

        return ($endValue / $startValue) - 1.0;
    }

    public function relativeReturn(Benchmark $benchmark, float $fundReturn, Carbon|string $startDate, Carbon|string $endDate): float
    {
        $benchmarkReturn = $this->periodReturn($benchmark, $startDate, $endDate);

        return $fundReturn - $benchmarkReturn;
    }

    public function cagr(Benchmark $benchmark, Carbon|string $startDate, Carbon|string $endDate): float
    {
        $start = $this->parseDate($startDate);
        $end = $this->parseDate($endDate);

        if ($start->greaterThan($end)) {
            throw new InvalidArgumentException("Start date cannot be after end date.");
        }

        $startValue = $this->resolveValue($benchmark, $start);

        if ($startValue === 0.0) {
            throw new InvalidArgumentException("Starting benchmark value is zero.");
        }

        $endValue = $this->resolveValue($benchmark, $end);

        $days = $start->diffInDays($end);

        if ($days === 0) {
            throw new InvalidArgumentException("Date range must be greater than zero days for CAGR.");
        }

        $years = $days / 365.25;

        return pow(($endValue / $startValue), (1 / $years)) - 1.0;
    }

    public function trackingDifference(Benchmark $benchmark, float $fundCagr, Carbon|string $startDate, Carbon|string $endDate): float
    {
        $benchmarkCagr = $this->cagr($benchmark, $startDate, $endDate);

        return $fundCagr - $benchmarkCagr;
    }

    public function beta(MutualFund $fund, Benchmark $benchmark, Carbon|string $startDate, Carbon|string $endDate): float
    {
        $start = $this->parseDate($startDate);
        $end = $this->parseDate($endDate);

        if ($start->greaterThan($end)) {
            throw new InvalidArgumentException("Start date cannot be after end date.");
        }

        $alignedReturns = $this->getAlignedDailyReturns($fund, $benchmark, $start, $end);

        $n = count($alignedReturns);

        $fundReturns = array_column($alignedReturns, 'fund');
        $benchmarkReturns = array_column($alignedReturns, 'benchmark');

        $meanFund = array_sum($fundReturns) / $n;
        $meanBenchmark = array_sum($benchmarkReturns) / $n;

        $covarianceSum = 0;
        $benchmarkVarianceSum = 0;

        foreach ($alignedReturns as $returns) {
            $fundDiff = $returns['fund'] - $meanFund;
            $benchmarkDiff = $returns['benchmark'] - $meanBenchmark;

            $covarianceSum += ($fundDiff * $benchmarkDiff);
            $benchmarkVarianceSum += pow($benchmarkDiff, 2);
        }

        $benchmarkVariance = $benchmarkVarianceSum / ($n - 1);
        $covariance = $covarianceSum / ($n - 1);

        if ($benchmarkVariance == 0.0) {
            throw new InvalidArgumentException("Benchmark return variance is zero.");
        }

        return $covariance / $benchmarkVariance;
    }

    public function trackingError(MutualFund $fund, Benchmark $benchmark, Carbon|string $startDate, Carbon|string $endDate): float
    {
        $start = $this->parseDate($startDate);
        $end = $this->parseDate($endDate);

        if ($start->greaterThan($end)) {
            throw new InvalidArgumentException("Start date cannot be after end date.");
        }

        $alignedReturns = $this->getAlignedDailyReturns($fund, $benchmark, $start, $end);

        $activeReturns = [];
        foreach ($alignedReturns as $returns) {
            $activeReturns[] = $returns['fund'] - $returns['benchmark'];
        }

        $n = count($activeReturns);

        $mean = array_sum($activeReturns) / $n;

        $sumSq = 0;
        foreach ($activeReturns as $return) {
            $sumSq += pow($return - $mean, 2);
        }

        $variance = $sumSq / ($n - 1);
        $stdDev = sqrt($variance);

        return $stdDev * sqrt(252);
    }

    private function getAlignedDailyReturns(MutualFund $fund, Benchmark $benchmark, Carbon $start, Carbon $end): array
    {
        $fundNavs = MutualFundNav::where('mutual_fund_id', $fund->id)
            ->whereDate('nav_date', '>=', $start)
            ->whereDate('nav_date', '<=', $end)
            ->orderBy('nav_date', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->nav_date)->format('Y-m-d');
            });

        $benchmarkValues = BenchmarkValue::where('benchmark_id', $benchmark->id)
            ->whereDate('valuation_date', '>=', $start)
            ->whereDate('valuation_date', '<=', $end)
            ->orderBy('valuation_date', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->valuation_date)->format('Y-m-d');
            });

        $pairedObservations = [];

        foreach ($fundNavs as $date => $nav) {
            if ($benchmarkValues->has($date)) {
                $pairedObservations[] = [
                    'nav' => (float) $nav->nav,
                    'benchmark' => (float) $benchmarkValues->get($date)->value,
                ];
            }
        }

        if (count($pairedObservations) < 3) {
            throw new MissingMarketDataException("Insufficient paired observations for return calculation.");
        }

        $alignedReturns = [];
        $previousNav = null;
        $previousBenchmark = null;

        foreach ($pairedObservations as $observation) {
            $currentNav = $observation['nav'];
            $currentBenchmark = $observation['benchmark'];

            if ($previousNav !== null && $previousBenchmark !== null) {
                if ($previousNav == 0 || $previousBenchmark == 0) {
                    throw new InvalidArgumentException("Zero value encountered in previous observation.");
                }

                $fundReturn = ($currentNav / $previousNav) - 1.0;
                $benchmarkReturn = ($currentBenchmark / $previousBenchmark) - 1.0;

                $alignedReturns[] = [
                    'fund' => $fundReturn,
                    'benchmark' => $benchmarkReturn,
                ];
            }

            $previousNav = $currentNav;
            $previousBenchmark = $currentBenchmark;
        }

        return $alignedReturns;
    }

    private function parseDate(Carbon|string $date): Carbon
    {
        if ($date instanceof Carbon) {
            return $date;
        }

        try {
            return Carbon::parse($date);
        } catch (Throwable $e) {
            throw new InvalidArgumentException("Invalid date format provided.");
        }
    }
}
