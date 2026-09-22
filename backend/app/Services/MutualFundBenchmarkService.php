<?php

namespace App\Services;

use App\Exceptions\MissingMarketDataException;
use App\Models\Benchmark;
use App\Models\BenchmarkValue;
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
