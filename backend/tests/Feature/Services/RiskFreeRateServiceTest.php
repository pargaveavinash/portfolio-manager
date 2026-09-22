<?php

namespace Tests\Feature\Services;

use App\Domain\RateResolutionPolicy;
use App\Exceptions\MissingMarketDataException;
use App\Models\RiskFreeRate;
use App\Models\RiskFreeRateValue;
use App\Services\RiskFreeRateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use InvalidArgumentException;

class RiskFreeRateServiceTest extends TestCase
{
    use RefreshDatabase;

    private RiskFreeRateService $service;
    private RiskFreeRate $rate;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(RiskFreeRateService::class);
        $this->rate = RiskFreeRate::create([
            'name' => '91-Day Treasury Bill',
            'code' => 'TBILL_91D',
            'source' => 'FBIL',
        ]);
    }

    private function createValues(array $values): void
    {
        foreach ($values as $date => $rate) {
            RiskFreeRateValue::create([
                'risk_free_rate_id' => $this->rate->id,
                'valuation_date' => $date,
                'rate' => $rate,
            ]);
        }
    }

    public function test_resolves_exact_date_with_strict_policy(): void
    {
        $this->createValues([
            '2026-01-01' => 0.065000,
        ]);

        $policy = RateResolutionPolicy::strict();
        $date = Carbon::parse('2026-01-01');

        $resolved = $this->service->resolveRate($this->rate, $date, $policy);

        $this->assertEquals(0.065000, $resolved);
    }

    public function test_fails_resolution_when_exact_date_missing_with_strict_policy(): void
    {
        $this->createValues([
            '2026-01-01' => 0.065000,
        ]);

        $policy = RateResolutionPolicy::strict();
        $date = Carbon::parse('2026-01-02');

        $this->expectException(MissingMarketDataException::class);
        $this->expectExceptionMessage('No risk-free rate found for TBILL_91D on or before 2026-01-02 within lookback period.');

        $this->service->resolveRate($this->rate, $date, $policy);
    }

    public function test_resolves_previous_date_within_lookback_period(): void
    {
        $this->createValues([
            '2026-01-01' => 0.065000,
            // 2026-01-02 is missing
        ]);

        $policy = RateResolutionPolicy::lookback(3); // 3 days lookback
        $date = Carbon::parse('2026-01-02');

        $resolved = $this->service->resolveRate($this->rate, $date, $policy);

        $this->assertEquals(0.065000, $resolved);
    }

    public function test_fails_when_previous_date_exceeds_lookback_period(): void
    {
        $this->createValues([
            '2026-01-01' => 0.065000,
        ]);

        $policy = RateResolutionPolicy::lookback(3); // 3 days lookback
        $date = Carbon::parse('2026-01-05'); // 4 days later

        $this->expectException(MissingMarketDataException::class);

        $this->service->resolveRate($this->rate, $date, $policy);
    }

    public function test_uses_fallback_when_rate_missing_and_no_lookback(): void
    {
        $policy = RateResolutionPolicy::fallback(0.060000);
        $date = Carbon::parse('2026-01-01');

        $resolved = $this->service->resolveRate($this->rate, $date, $policy);

        $this->assertEquals(0.060000, $resolved);
    }

    public function test_uses_fallback_when_lookback_exhausted(): void
    {
        $this->createValues([
            '2026-01-01' => 0.065000,
        ]);

        $policy = RateResolutionPolicy::lookbackWithFallback(3, 0.060000);
        $date = Carbon::parse('2026-01-05'); // 4 days later, lookback fails

        $resolved = $this->service->resolveRate($this->rate, $date, $policy);

        $this->assertEquals(0.060000, $resolved);
    }

    public function test_converts_annualized_yield_to_daily_effective_rate(): void
    {
        // 6.5% annual rate
        $annualRate = 0.065000;
        
        // Expected: (1 + 0.065000)^(1/365) - 1
        $dailyEffective = $this->service->calculateDailyEffectiveRate($annualRate);

        $expected = pow(1 + 0.065000, 1 / 365) - 1;
        $this->assertEqualsWithDelta($expected, $dailyEffective, 0.000000001);
    }

    public function test_calculates_period_return_from_single_rate(): void
    {
        // 6.5% annual rate
        $annualRate = 0.065000;
        $days = 30;

        // Expected: (1 + 0.065000)^(30/365) - 1
        $periodReturn = $this->service->calculatePeriodReturn($annualRate, $days);

        $expected = pow(1 + 0.065000, 30 / 365) - 1;
        $this->assertEqualsWithDelta($expected, $periodReturn, 0.000000001);
    }

    public function test_calculates_annualized_cagr_using_actual_365_25(): void
    {
        // Period return over exactly 1 year (365 days)
        // If the period return was 6.5%, the CAGR should be slightly less because CAGR uses 365.25
        $periodReturn = 0.065000;
        $days = 365;

        // Expected: (1 + 0.065)^(365.25 / 365) - 1
        $cagr = $this->service->calculateAnnualizedCagr($periodReturn, $days);

        $expected = pow(1 + $periodReturn, 365.25 / $days) - 1;
        $this->assertEqualsWithDelta($expected, $cagr, 0.000000001);
    }

    public function test_throws_exception_if_start_date_is_after_or_equal_to_end_date(): void
    {
        $startDate = Carbon::parse('2026-01-05');
        $endDate = Carbon::parse('2026-01-01');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Start date must be strictly before end date.');

        // Assuming a method like calculatePeriodReturnBetweenDates(rate, start, end, policy) exists
        // Or if it's just calculatePeriodReturn, it might not take dates directly but just days.
        // Let's assume the service provides a method to calculate compounded return between dates:
        $policy = RateResolutionPolicy::strict();
        $this->service->calculatePeriodReturnBetweenDates($this->rate, $startDate, $endDate, $policy);
    }

    public function test_compounds_correctly_when_rate_changes_within_period(): void
    {
        $this->createValues([
            '2026-01-01' => 0.065000,
            '2026-01-15' => 0.070000,
        ]);

        $startDate = Carbon::parse('2026-01-01');
        $endDate = Carbon::parse('2026-01-31');
        $policy = RateResolutionPolicy::lookback(5);

        // Expected compounding:
        // Segment 1: Jan 1 to Jan 15 (14 days) @ 6.5%
        // Segment 2: Jan 15 to Jan 31 (16 days) @ 7.0%
        $dailyEffective1 = pow(1 + 0.065000, 1 / 365) - 1;
        $dailyEffective2 = pow(1 + 0.070000, 1 / 365) - 1;
        
        $expectedReturn = (pow(1 + $dailyEffective1, 14) * pow(1 + $dailyEffective2, 16)) - 1;

        $actualReturn = $this->service->calculatePeriodReturnBetweenDates($this->rate, $startDate, $endDate, $policy);

        $this->assertEqualsWithDelta($expectedReturn, $actualReturn, 0.000000001);
    }

    public function test_compounds_correctly_over_weekends_and_holidays(): void
    {
        // Missing values on weekends, lookback covers them
        $this->createValues([
            '2026-01-02' => 0.065000, // Friday
            // 3, 4 are weekend
            '2026-01-05' => 0.066000, // Monday
        ]);

        $startDate = Carbon::parse('2026-01-02');
        $endDate = Carbon::parse('2026-01-06');
        $policy = RateResolutionPolicy::lookback(3);

        // Segment 1: Jan 2 to Jan 5 (3 days) @ 6.5%
        // Segment 2: Jan 5 to Jan 6 (1 day) @ 6.6%
        $dailyEffective1 = pow(1 + 0.065000, 1 / 365) - 1;
        $dailyEffective2 = pow(1 + 0.066000, 1 / 365) - 1;
        
        $expectedReturn = (pow(1 + $dailyEffective1, 3) * pow(1 + $dailyEffective2, 1)) - 1;

        $actualReturn = $this->service->calculatePeriodReturnBetweenDates($this->rate, $startDate, $endDate, $policy);

        $this->assertEqualsWithDelta($expectedReturn, $actualReturn, 0.000000001);
    }
}
