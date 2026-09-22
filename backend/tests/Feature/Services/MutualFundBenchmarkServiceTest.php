<?php

namespace Tests\Feature\Services;

use App\Exceptions\MissingMarketDataException;
use App\Models\Benchmark;
use App\Models\BenchmarkValue;
use App\Services\MutualFundBenchmarkService;
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
}
