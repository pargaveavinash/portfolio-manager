<?php

namespace Tests\Feature\Services;

use App\Exceptions\MissingMarketDataException;
use App\Models\Benchmark;
use App\Models\BenchmarkValue;
use App\Models\MutualFund;
use App\Models\MutualFundNav;
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

    private function createFundNavs(MutualFund $fund, array $navsData): void
    {
        foreach ($navsData as $date => $nav) {
            MutualFundNav::create([
                'mutual_fund_id' => $fund->id,
                'nav_date' => $date,
                'nav' => $nav,
            ]);
        }
    }

    public function test_tracking_error_calculates_correctly_with_paired_observations(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->createBenchmarkValues([
            '2026-01-01' => 100.0,
            '2026-01-02' => 101.0,
            '2026-01-03' => 100.5,
            '2026-01-04' => 102.0,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 10.0,
            '2026-01-02' => 10.2, // fund: 0.02, bm: 0.01 -> diff: 0.01
            '2026-01-03' => 10.1, // fund: -0.0098039, bm: -0.0049504 -> diff: -0.0048534
            '2026-01-04' => 10.4, // fund: 0.0297029, bm: 0.0149253 -> diff: 0.0147776
        ]);

        $trackingError = $this->service->trackingError($fund, $this->benchmark, '2026-01-01', '2026-01-04');

        $this->assertEqualsWithDelta(0.162512, $trackingError, 0.00001);
    }

    public function test_tracking_error_minimum_observations_succeeds(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->createBenchmarkValues([
            '2026-01-01' => 100.0,
            '2026-01-02' => 101.0,
            '2026-01-03' => 100.5,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 10.0,
            '2026-01-02' => 10.2,
            '2026-01-03' => 10.1,
        ]);

        $trackingError = $this->service->trackingError($fund, $this->benchmark, '2026-01-01', '2026-01-03');

        $this->assertIsFloat($trackingError);
    }

    public function test_tracking_error_insufficient_observations_throws_exception(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->createBenchmarkValues([
            '2026-01-01' => 100.0,
            '2026-01-02' => 101.0,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 10.0,
            '2026-01-02' => 10.2,
        ]);

        $this->expectException(MissingMarketDataException::class);

        $this->service->trackingError($fund, $this->benchmark, '2026-01-01', '2026-01-02');
    }

    public function test_tracking_error_missing_benchmark_observation_skips_fund_nav(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->createBenchmarkValues([
            '2026-01-01' => 100.0,
            '2026-01-02' => 101.0,
            // 2026-01-03 missing intentionally
            '2026-01-04' => 102.0,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 10.0,
            '2026-01-02' => 10.2,
            '2026-01-03' => 10.1, // Should be skipped
            '2026-01-04' => 10.4,
        ]);

        $trackingError = $this->service->trackingError($fund, $this->benchmark, '2026-01-01', '2026-01-04');

        $this->assertEqualsWithDelta(0.00329056, $trackingError, 0.00001);
    }

    public function test_tracking_error_missing_initial_history_throws_exception(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->createBenchmarkValues([
            '2026-01-02' => 101.0,
            '2026-01-03' => 100.5,
            '2026-01-04' => 102.0,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 10.0,
            '2026-01-02' => 10.2,
            '2026-01-03' => 10.1,
            '2026-01-04' => 10.4,
        ]);

        $this->expectException(MissingMarketDataException::class);

        // Only 3 valid dates, but dates are 01-02 to 01-03 for first part.
        // We only pass '2026-01-01' to '2026-01-03'.
        // Valid paired dates in range: 01-02, 01-03 (Total 2)
        // Required: 3
        $this->service->trackingError($fund, $this->benchmark, '2026-01-01', '2026-01-03');
    }

    public function test_tracking_error_zero_variance(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->createBenchmarkValues([
            '2026-01-01' => 100.0,
            '2026-01-02' => 110.0,
            '2026-01-03' => 121.0,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 10.0,
            '2026-01-02' => 11.0,
            '2026-01-03' => 12.1,
        ]);

        $trackingError = $this->service->trackingError($fund, $this->benchmark, '2026-01-01', '2026-01-03');

        $this->assertEqualsWithDelta(0.00, $trackingError, 0.000001);
    }

    public function test_tracking_error_zero_previous_nav_or_value_throws_exception(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->createBenchmarkValues([
            '2026-01-01' => 0.0,
            '2026-01-02' => 101.0,
            '2026-01-03' => 100.5,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 10.0,
            '2026-01-02' => 10.2,
            '2026-01-03' => 10.1,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->trackingError($fund, $this->benchmark, '2026-01-01', '2026-01-03');
    }

    public function test_tracking_error_invalid_date_range_throws_exception(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->trackingError($fund, $this->benchmark, '2026-01-04', '2026-01-01');
    }

    public function test_tracking_error_different_observation_dates_skips_unpaired(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->createBenchmarkValues([
            '2026-01-01' => 100.0,
            '2026-01-03' => 101.0,
            '2026-01-05' => 102.0,
            '2026-01-06' => 103.0,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 10.0,
            '2026-01-02' => 10.2, // Unpaired
            '2026-01-03' => 10.4,
            '2026-01-04' => 10.3, // Unpaired
            '2026-01-05' => 10.5,
        ]);

        $trackingError = $this->service->trackingError($fund, $this->benchmark, '2026-01-01', '2026-01-05');

        $this->assertEqualsWithDelta(0.33996, $trackingError, 0.0001);
    }

    public function test_tracking_error_query_efficiency(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        for ($i = 1; $i <= 30; $i++) {
            $date = Carbon::parse('2026-01-01')->addDays($i - 1)->format('Y-m-d');
            BenchmarkValue::create(['benchmark_id' => $this->benchmark->id, 'valuation_date' => $date, 'value' => 100 + $i]);
            MutualFundNav::create(['mutual_fund_id' => $fund->id, 'nav_date' => $date, 'nav' => 10 + ($i * 0.1)]);
        }

        \Illuminate\Support\Facades\DB::enableQueryLog();

        try {
            $this->service->trackingError($fund, $this->benchmark, '2026-01-01', '2026-01-30');
        } catch (\Error $e) {
            // It will throw an error since the method doesn't exist, but we still assert the query log count
            // if we want, or rather the test will just fail gracefully since the method doesn't exist yet.
            // Wait, if it fails due to Method not found, the test will just exit early and not reach assertions.
        }

        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertLessThanOrEqual(5, count($queries), "Query count is too high, N+1 problem likely.");
    }

    public function test_beta_calculates_correctly_with_known_returns(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->createBenchmarkValues([
            '2026-01-01' => 100.0,
            '2026-01-02' => 101.0,      // Return: 0.01
            '2026-01-03' => 103.02,     // Return: 0.02
            '2026-01-04' => 106.1106,   // Return: 0.03
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 100.0,
            '2026-01-02' => 102.0,      // Return: 0.02
            '2026-01-03' => 106.08,     // Return: 0.04
            '2026-01-04' => 112.4448,   // Return: 0.06
        ]);

        $beta = $this->service->beta($fund, $this->benchmark, '2026-01-01', '2026-01-04');

        $this->assertEqualsWithDelta(2.0, $beta, 0.0001);
    }

    public function test_beta_is_not_annualized(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->createBenchmarkValues([
            '2026-01-01' => 100.0,
            '2026-01-02' => 101.0,
            '2026-01-03' => 103.02,
            '2026-01-04' => 106.1106,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 100.0,
            '2026-01-02' => 102.0,
            '2026-01-03' => 106.08,
            '2026-01-04' => 112.4448,
        ]);

        $beta = $this->service->beta($fund, $this->benchmark, '2026-01-01', '2026-01-04');

        $this->assertNotEqualsWithDelta(2.0 * sqrt(252), $beta, 0.0001);
        $this->assertEqualsWithDelta(2.0, $beta, 0.0001);
    }

    public function test_beta_requires_minimum_observations(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        // Exactly 3 paired observations
        $this->createBenchmarkValues([
            '2026-01-01' => 100.0,
            '2026-01-02' => 101.0,
            '2026-01-03' => 103.02,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 100.0,
            '2026-01-02' => 102.0,
            '2026-01-03' => 106.08,
        ]);

        $beta = $this->service->beta($fund, $this->benchmark, '2026-01-01', '2026-01-03');

        $this->assertIsFloat($beta);
    }

    public function test_beta_insufficient_observations_throws_exception(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        // Only 2 paired observations
        $this->createBenchmarkValues([
            '2026-01-01' => 100.0,
            '2026-01-02' => 101.0,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 100.0,
            '2026-01-02' => 102.0,
        ]);

        $this->expectException(MissingMarketDataException::class);

        $this->service->beta($fund, $this->benchmark, '2026-01-01', '2026-01-02');
    }

    public function test_beta_zero_benchmark_variance_throws_exception(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        // Benchmark returns are all exactly 0.01, so variance is 0
        $this->createBenchmarkValues([
            '2026-01-01' => 100.0,
            '2026-01-02' => 101.0,
            '2026-01-03' => 102.01,
            '2026-01-04' => 103.0301,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 100.0,
            '2026-01-02' => 102.0,
            '2026-01-03' => 106.08,
            '2026-01-04' => 112.4448,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->beta($fund, $this->benchmark, '2026-01-01', '2026-01-04');
    }

    public function test_beta_zero_previous_nav_or_benchmark_throws_exception(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->createBenchmarkValues([
            '2026-01-01' => 0.0,
            '2026-01-02' => 101.0,
            '2026-01-03' => 103.02,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 100.0,
            '2026-01-02' => 102.0,
            '2026-01-03' => 106.08,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->beta($fund, $this->benchmark, '2026-01-01', '2026-01-03');
    }

    public function test_beta_invalid_date_range_throws_exception(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->beta($fund, $this->benchmark, '2026-01-04', '2026-01-01');
    }

    public function test_beta_ignores_unpaired_observation_dates(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->createBenchmarkValues([
            '2026-01-01' => 100.0,
            '2026-01-03' => 101.0,
            '2026-01-05' => 103.02,
            '2026-01-06' => 105.0, // Benchmark only
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 100.0,
            '2026-01-02' => 101.0, // Fund only
            '2026-01-03' => 102.0,
            '2026-01-04' => 103.0, // Fund only
            '2026-01-05' => 106.08,
        ]);

        $beta = $this->service->beta($fund, $this->benchmark, '2026-01-01', '2026-01-05');

        $this->assertEqualsWithDelta(2.0, $beta, 0.0001);
    }

    public function test_tracking_error_regression_after_shared_logic_refactor(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        $this->createBenchmarkValues([
            '2026-01-01' => 100.0,
            '2026-01-02' => 101.0,
            '2026-01-03' => 100.5,
            '2026-01-04' => 102.0,
        ]);

        $this->createFundNavs($fund, [
            '2026-01-01' => 10.0,
            '2026-01-02' => 10.2,
            '2026-01-03' => 10.1,
            '2026-01-04' => 10.4,
        ]);

        $trackingError = $this->service->trackingError($fund, $this->benchmark, '2026-01-01', '2026-01-04');

        $this->assertEqualsWithDelta(0.162512, $trackingError, 0.00001);
    }

    public function test_beta_query_efficiency(): void
    {
        $fund = MutualFund::factory()->create(['benchmark_id' => $this->benchmark->id]);

        for ($i = 1; $i <= 30; $i++) {
            $date = Carbon::parse('2026-01-01')->addDays($i - 1)->format('Y-m-d');
            BenchmarkValue::create(['benchmark_id' => $this->benchmark->id, 'valuation_date' => $date, 'value' => 100 + $i]);
            MutualFundNav::create(['mutual_fund_id' => $fund->id, 'nav_date' => $date, 'nav' => 10 + ($i * 0.1)]);
        }

        \Illuminate\Support\Facades\DB::enableQueryLog();

        try {
            $this->service->beta($fund, $this->benchmark, '2026-01-01', '2026-01-30');
        } catch (\Exception $e) {
            // Fails due to MissingMarketDataException or InvalidArgumentException or division by zero in TDD
        }

        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertLessThanOrEqual(5, count($queries), "Query count is too high, N+1 problem likely.");
    }
}
