<?php

namespace Tests\Feature\Services;

use App\Exceptions\MissingMarketDataException;
use App\Models\Benchmark;
use App\Models\BenchmarkValue;
use App\Services\MutualFundBenchmarkService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class MutualFundBenchmarkServiceTest extends TestCase
{
    use RefreshDatabase;

    private MutualFundBenchmarkService $service;
    private Benchmark $benchmark;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->benchmark = Benchmark::create([
            'name' => 'NIFTY 50 TRI',
            'code' => 'NIFTY50_TRI',
            'variant' => 'TRI',
        ]);
        
        // This will fail because the class doesn't exist yet, which is expected for TDD.
        $this->service = new MutualFundBenchmarkService();
    }

    private function createBenchmarkValues(array $valuesData): void
    {
        foreach ($valuesData as $date => $value) {
            BenchmarkValue::create([
                'benchmark_id' => $this->benchmark->id,
                'valuation_date' => $date,
                'value' => $value,
            ]);
        }
    }

    public function test_resolves_exact_benchmark_value(): void
    {
        $this->createBenchmarkValues([
            '2026-01-01' => 100.00,
            '2026-02-01' => 105.00,
        ]);

        $value = $this->service->resolveValue($this->benchmark, '2026-02-01');

        $this->assertEquals(105.00, $value);
    }

    public function test_resolves_latest_value_on_or_before_requested_date(): void
    {
        $this->createBenchmarkValues([
            '2026-01-01' => 100.00,
            '2026-02-01' => 105.00,
            '2026-02-20' => 110.00,
        ]);

        $value = $this->service->resolveValue($this->benchmark, '2026-02-15');

        $this->assertEquals(105.00, $value);
    }

    public function test_never_uses_a_future_benchmark_value(): void
    {
        $this->createBenchmarkValues([
            '2026-02-20' => 110.00,
        ]);

        $this->expectException(MissingMarketDataException::class);

        $this->service->resolveValue($this->benchmark, '2026-02-15');
    }

    public function test_no_benchmark_history_throws_exception(): void
    {
        $this->expectException(MissingMarketDataException::class);

        $this->service->resolveValue($this->benchmark, '2026-01-01');
    }

    public function test_calculates_benchmark_period_return(): void
    {
        $this->createBenchmarkValues([
            '2026-01-01' => 100.00,
            '2026-02-01' => 120.00,
        ]);

        $return = $this->service->periodReturn($this->benchmark, '2026-01-01', '2026-02-01');

        // (120 / 100) - 1 = 0.20
        $this->assertEqualsWithDelta(0.20, $return, 0.000001);
    }

    public function test_period_return_uses_valuation_date_resolution(): void
    {
        $this->createBenchmarkValues([
            '2025-12-30' => 100.00, // Used for 2026-01-01
            '2026-01-02' => 105.00,
            '2026-01-30' => 120.00, // Used for 2026-02-01
            '2026-02-02' => 125.00,
        ]);

        $return = $this->service->periodReturn($this->benchmark, '2026-01-01', '2026-02-01');

        $this->assertEqualsWithDelta(0.20, $return, 0.000001);
    }

    public function test_invalid_date_range_throws_exception(): void
    {
        $this->createBenchmarkValues([
            '2026-01-01' => 100.00,
            '2026-02-01' => 120.00,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->periodReturn($this->benchmark, '2026-02-01', '2026-01-01');
    }

    public function test_invalid_date_input_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->resolveValue($this->benchmark, 'invalid-date');
    }

    public function test_zero_start_benchmark_value_throws_exception(): void
    {
        $this->createBenchmarkValues([
            '2026-01-01' => 0.00,
            '2026-02-01' => 120.00,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->periodReturn($this->benchmark, '2026-01-01', '2026-02-01');
    }

    public function test_precision_is_maintained(): void
    {
        $this->createBenchmarkValues([
            '2026-01-01' => 103.456,
            '2026-02-01' => 107.892,
        ]);

        $return = $this->service->periodReturn($this->benchmark, '2026-01-01', '2026-02-01');

        // (107.892 / 103.456) - 1 = 0.04287813...
        $this->assertEqualsWithDelta(0.04287813, $return, 0.000001);
    }

    public function test_relative_return_positive(): void
    {
        $this->createBenchmarkValues([
            '2026-01-01' => 100.00,
            '2026-02-01' => 115.00, // benchmark return: 0.15
        ]);

        $fundReturn = 0.20;

        $relativeReturn = $this->service->relativeReturn($this->benchmark, $fundReturn, '2026-01-01', '2026-02-01');

        $this->assertEqualsWithDelta(0.05, $relativeReturn, 0.000001);
    }

    public function test_relative_return_negative(): void
    {
        $this->createBenchmarkValues([
            '2026-01-01' => 100.00,
            '2026-02-01' => 125.00, // benchmark return: 0.25
        ]);

        $fundReturn = 0.10;

        $relativeReturn = $this->service->relativeReturn($this->benchmark, $fundReturn, '2026-01-01', '2026-02-01');

        $this->assertEqualsWithDelta(-0.15, $relativeReturn, 0.000001);
    }

    public function test_relative_return_zero(): void
    {
        $this->createBenchmarkValues([
            '2026-01-01' => 100.00,
            '2026-02-01' => 110.00, // benchmark return: 0.10
        ]);

        $fundReturn = 0.10;

        $relativeReturn = $this->service->relativeReturn($this->benchmark, $fundReturn, '2026-01-01', '2026-02-01');

        $this->assertEqualsWithDelta(0.00, $relativeReturn, 0.000001);
    }

    public function test_cagr_normal_multi_year(): void
    {
        $this->createBenchmarkValues([
            '2024-01-01' => 100.00,
            '2026-01-01' => 121.00,
        ]);

        $days = Carbon::parse('2024-01-01')->diffInDays(Carbon::parse('2026-01-01'));
        $years = $days / 365.25;
        $expectedCagr = pow((121.00 / 100.00), (1 / $years)) - 1;

        $cagr = $this->service->cagr($this->benchmark, '2024-01-01', '2026-01-01');

        $this->assertEqualsWithDelta($expectedCagr, $cagr, 0.000001);
    }

    public function test_cagr_missing_exact_dates_uses_resolved_values(): void
    {
        $this->createBenchmarkValues([
            '2023-12-30' => 100.00, // resolves for 2024-01-01
            '2026-01-05' => 121.00, // resolves for 2026-01-10
        ]);

        $days = Carbon::parse('2024-01-01')->diffInDays(Carbon::parse('2026-01-10'));
        $years = $days / 365.25;
        $expectedCagr = pow((121.00 / 100.00), (1 / $years)) - 1;

        $cagr = $this->service->cagr($this->benchmark, '2024-01-01', '2026-01-10');

        $this->assertEqualsWithDelta($expectedCagr, $cagr, 0.000001);
    }

    public function test_cagr_invalid_date_range_throws_exception(): void
    {
        $this->createBenchmarkValues([
            '2026-01-01' => 100.00,
            '2026-02-01' => 120.00,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->cagr($this->benchmark, '2026-02-01', '2026-01-01');
    }

    public function test_cagr_missing_benchmark_data_throws_exception(): void
    {
        $this->createBenchmarkValues([
            '2025-01-01' => 120.00,
        ]);

        $this->expectException(MissingMarketDataException::class);

        $this->service->cagr($this->benchmark, '2024-01-01', '2025-01-01');
    }

    public function test_cagr_maintains_precision(): void
    {
        $this->createBenchmarkValues([
            '2023-01-01' => 102.345,
            '2026-01-01' => 145.678,
        ]);

        $days = Carbon::parse('2023-01-01')->diffInDays(Carbon::parse('2026-01-01'));
        $years = $days / 365.25;
        $expectedCagr = pow((145.678 / 102.345), (1 / $years)) - 1;

        $cagr = $this->service->cagr($this->benchmark, '2023-01-01', '2026-01-01');

        $this->assertEqualsWithDelta($expectedCagr, $cagr, 0.000001);
    }

    public function test_tracking_difference_positive(): void
    {
        $this->createBenchmarkValues([
            '2024-01-01' => 100.00,
            '2026-01-01' => 121.00,
        ]);

        $days = Carbon::parse('2024-01-01')->diffInDays(Carbon::parse('2026-01-01'));
        $years = $days / 365.25;
        $benchmarkCagr = pow((121.00 / 100.00), (1 / $years)) - 1;

        $fundCagr = $benchmarkCagr + 0.05;

        $trackingDifference = $this->service->trackingDifference($this->benchmark, $fundCagr, '2024-01-01', '2026-01-01');

        $this->assertEqualsWithDelta(0.05, $trackingDifference, 0.000001);
    }

    public function test_tracking_difference_negative(): void
    {
        $this->createBenchmarkValues([
            '2024-01-01' => 100.00,
            '2026-01-01' => 121.00,
        ]);

        $days = Carbon::parse('2024-01-01')->diffInDays(Carbon::parse('2026-01-01'));
        $years = $days / 365.25;
        $benchmarkCagr = pow((121.00 / 100.00), (1 / $years)) - 1;

        $fundCagr = $benchmarkCagr - 0.02;

        $trackingDifference = $this->service->trackingDifference($this->benchmark, $fundCagr, '2024-01-01', '2026-01-01');

        $this->assertEqualsWithDelta(-0.02, $trackingDifference, 0.000001);
    }

    public function test_tracking_difference_zero(): void
    {
        $this->createBenchmarkValues([
            '2024-01-01' => 100.00,
            '2026-01-01' => 121.00,
        ]);

        $days = Carbon::parse('2024-01-01')->diffInDays(Carbon::parse('2026-01-01'));
        $years = $days / 365.25;
        $benchmarkCagr = pow((121.00 / 100.00), (1 / $years)) - 1;

        $fundCagr = $benchmarkCagr;

        $trackingDifference = $this->service->trackingDifference($this->benchmark, $fundCagr, '2024-01-01', '2026-01-01');

        $this->assertEqualsWithDelta(0.00, $trackingDifference, 0.000001);
    }

    public function test_tracking_difference_uses_cagr_logic(): void
    {
        $this->createBenchmarkValues([
            '2023-12-30' => 100.00,
            '2026-01-05' => 121.00,
        ]);

        $days = Carbon::parse('2024-01-01')->diffInDays(Carbon::parse('2026-01-10'));
        $years = $days / 365.25;
        $benchmarkCagr = pow((121.00 / 100.00), (1 / $years)) - 1;

        $fundCagr = 0.15;
        $expectedTrackingDifference = $fundCagr - $benchmarkCagr;

        $trackingDifference = $this->service->trackingDifference($this->benchmark, $fundCagr, '2024-01-01', '2026-01-10');

        $this->assertEqualsWithDelta($expectedTrackingDifference, $trackingDifference, 0.000001);
    }

    public function test_tracking_difference_missing_benchmark_data_throws_exception(): void
    {
        $this->createBenchmarkValues([
            '2026-01-01' => 100.00,
        ]);

        $this->expectException(MissingMarketDataException::class);

        $this->service->trackingDifference($this->benchmark, 0.10, '2024-01-01', '2026-01-01');
    }

    public function test_tracking_difference_invalid_date_range_throws_exception(): void
    {
        $this->createBenchmarkValues([
            '2024-01-01' => 100.00,
            '2026-01-01' => 121.00,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->trackingDifference($this->benchmark, 0.10, '2026-01-01', '2024-01-01');
    }

    public function test_tracking_difference_maintains_precision(): void
    {
        $this->createBenchmarkValues([
            '2024-01-01' => 101.123,
            '2026-01-01' => 134.567,
        ]);

        $days = Carbon::parse('2024-01-01')->diffInDays(Carbon::parse('2026-01-01'));
        $years = $days / 365.25;
        $benchmarkCagr = pow((134.567 / 101.123), (1 / $years)) - 1;

        $fundCagr = 0.1234567;
        $expectedTrackingDifference = $fundCagr - $benchmarkCagr;

        $trackingDifference = $this->service->trackingDifference($this->benchmark, $fundCagr, '2024-01-01', '2026-01-01');

        $this->assertEqualsWithDelta($expectedTrackingDifference, $trackingDifference, 0.000001);
    }
}
