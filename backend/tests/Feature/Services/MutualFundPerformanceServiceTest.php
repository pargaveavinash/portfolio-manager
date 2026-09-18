<?php

namespace Tests\Feature\Services;

use App\Exceptions\MissingMarketDataException;
use App\Models\MutualFund;
use App\Models\MutualFundNav;
use App\Services\MutualFundPerformanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MutualFundPerformanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private MutualFund $fund;
    private MutualFundPerformanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fund = MutualFund::factory()->create([
            'amc_name' => 'Test AMC',
            'scheme_name' => 'Performance Test Fund',
        ]);
        
        // We instantiate the service that will be implemented
        // It will fail because the class doesn't exist yet, which is expected for TDD.
        $this->service = new MutualFundPerformanceService();
    }

    private function createNavs(array $navData): void
    {
        foreach ($navData as $date => $nav) {
            MutualFundNav::create([
                'mutual_fund_id' => $this->fund->id,
                'nav_date' => $date,
                'nav' => $nav,
            ]);
        }
    }

    public function test_nav_resolution_returns_latest_nav_on_or_before_requested_date()
    {
        $this->createNavs([
            '2026-09-15' => 145.0,
            '2026-09-18' => 150.0,
            '2026-09-22' => 160.0,
        ]);

        $resolvedNav = $this->service->resolveNav($this->fund, Carbon::parse('2026-09-20'));
        
        $this->assertNotNull($resolvedNav);
        $this->assertEquals(150.0, $resolvedNav->nav);
        $this->assertEquals('2026-09-18', substr($resolvedNav->nav_date, 0, 10));
    }

    public function test_no_forward_nav_is_used()
    {
        $this->createNavs([
            '2026-09-18' => 150.0,
            '2026-09-22' => 160.0,
        ]);

        $resolvedNav = $this->service->resolveNav($this->fund, Carbon::parse('2026-09-20'));

        $this->assertEquals(150.0, $resolvedNav->nav);
        
        // Before any NAVs
        $this->expectException(MissingMarketDataException::class);
        $this->service->resolveNav($this->fund, Carbon::parse('2026-09-17'));
    }

    public function test_period_return()
    {
        $this->createNavs([
            '2020-01-01' => 100.0,
            '2023-01-01' => 150.0,
        ]);

        $return = $this->service->periodReturn(
            $this->fund, 
            Carbon::parse('2020-01-01'), 
            Carbon::parse('2023-01-01')
        );

        $this->assertEqualsWithDelta(0.50, $return, 0.0001);
    }

    public function test_cagr_calculation()
    {
        $this->createNavs([
            '2020-01-01' => 100.0,
            '2023-01-01' => 150.0,
        ]);

        $cagr = $this->service->cagr(
            $this->fund,
            Carbon::parse('2020-01-01'),
            Carbon::parse('2023-01-01')
        );

        // The correct expected value is approximately 0.1446789525 based on actual days (1096)
        // CAGR = (150 / 100) ^ (1 / (1096 / 365.25)) - 1
        $this->assertEqualsWithDelta(0.1446789525, $cagr, 0.000001);
    }

    public function test_cagr_handles_insufficient_data_with_exception()
    {
        $this->createNavs([
            '2024-01-01' => 100.0,
            '2026-01-01' => 150.0, // Only 2 years of history
        ]);

        $this->expectException(MissingMarketDataException::class);

        // Requesting 5-year CAGR, meaning we ask for 2021-01-01 to 2026-01-01
        $this->service->cagr(
            $this->fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2026-01-01')
        );
    }

    public function test_invalid_cagr_starting_value()
    {
        $this->createNavs([
            '2020-01-01' => 0.0,
            '2023-01-01' => 150.0,
        ]);

        $this->expectException(MissingMarketDataException::class);
        $this->service->cagr(
            $this->fund,
            Carbon::parse('2020-01-01'),
            Carbon::parse('2023-01-01')
        );
    }

    public function test_rolling_returns_monthly_observation()
    {
        // Generate monthly observation points
        // 12-month rolling window over four requested observation dates
        // Needs 15 months of data
        $navs = [];
        $start = Carbon::parse('2020-01-01');
        for ($i = 0; $i <= 15; $i++) {
            $date = $start->copy()->addMonthsNoOverflow($i);
            $navs[$date->format('Y-m-d')] = 100.0 + ($i * 5); 
        }
        $this->createNavs($navs);

        // Calculate 12-month rolling return, observed monthly
        $rolling = $this->service->rollingReturns(
            $this->fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2021-04-01'), // 4 observations: Jan, Feb, Mar, Apr
            12 // 12-month rolling window
        );

        $this->assertCount(4, $rolling);
        
        // Observation 1: 2021-01-01 (vs 2020-01-01)
        // 2020-01-01 = 100.0, 2021-01-01 = 160.0
        // Return = 60.0%
        $this->assertEqualsWithDelta(0.60, $rolling[0]['return'], 0.0001);
        $this->assertEquals('2021-01-01', $rolling[0]['date']);
    }

    public function test_rolling_returns_does_not_fabricate_insufficient_windows()
    {
        $navs = [];
        $start = Carbon::parse('2020-06-01'); // Only 7 months of data before Jan 2021
        for ($i = 0; $i <= 10; $i++) {
            $date = $start->copy()->addMonthsNoOverflow($i);
            $navs[$date->format('Y-m-d')] = 100.0 + ($i * 5); 
        }
        $this->createNavs($navs);

        // 12-month rolling window returns should exclude insufficient windows rather than fabricate them
        $rolling = $this->service->rollingReturns(
            $this->fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2021-04-01'),
            12
        );

        // Since it cannot look back 12 months for earlier dates, those windows should be excluded
        // Wait, the earliest NAV is 2020-06-01.
        // For 2021-01-01, 12 months ago is 2020-01-01, which is missing.
        // So this window cannot be calculated and should not be in the results.
        $this->assertEmpty($rolling);
    }

    public function test_annualized_volatility()
    {
        // 5 days of NAV to calculate 4 daily returns
        $this->createNavs([
            '2021-01-01' => 100.0,
            '2021-01-02' => 101.0, // 1%
            '2021-01-03' => 100.0, // -0.99009%
            '2021-01-04' => 102.0, // 2%
            '2021-01-05' => 101.0, // -0.98039%
        ]);

        $volatility = $this->service->annualizedVolatility(
            $this->fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2021-01-05')
        );

        // Calculate expected value manually:
        // Returns: 0.01, -0.00990099, 0.02, -0.00980392
        // Mean = 0.00257377
        // Diff squared:
        // (0.01 - 0.00257377)^2 = 0.000055149
        // (-0.00990099 - 0.00257377)^2 = 0.000155615
        // (0.02 - 0.00257377)^2 = 0.000303673
        // (-0.00980392 - 0.00257377)^2 = 0.000153205
        // Sum = 0.000667642
        // Variance (sample, n-1 = 3) = 0.000222547
        // Std Dev = 0.014918
        // Annualized = 0.014918 * sqrt(252) = 0.236816 (23.68%)
        
        $this->assertNotNull($volatility);
        $this->assertEqualsWithDelta(0.2368, $volatility, 0.001);
    }

    public function test_maximum_drawdown()
    {
        $this->createNavs([
            '2021-01-01' => 100.0,
            '2021-02-01' => 120.0,
            '2021-03-01' => 110.0,
            '2021-04-01' => 90.0,
            '2021-05-01' => 115.0,
        ]);

        $maxDrawdown = $this->service->maximumDrawdown(
            $this->fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2021-05-01')
        );

        // Peak is 120, trough is 90
        // Drawdown = 90 / 120 - 1 = -0.25 (-25%)
        $this->assertEquals(-0.25, $maxDrawdown);
    }

    public function test_drawdown_recovery_does_not_erase_historical_max_drawdown()
    {
        $this->createNavs([
            '2021-01-01' => 100.0,
            '2021-02-01' => 120.0, // Peak 1
            '2021-03-01' => 90.0,  // Trough 1: 90/120 - 1 = -25%
            '2021-04-01' => 150.0, // Peak 2 (Recovery)
            '2021-05-01' => 135.0, // Trough 2: 135/150 - 1 = -10%
        ]);

        $maxDrawdown = $this->service->maximumDrawdown(
            $this->fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2021-05-01')
        );

        // Maximum drawdown should still be the -25% from Peak 1, despite the recovery and a later smaller drawdown
        $this->assertEquals(-0.25, $maxDrawdown);
    }

    public function test_maximum_drawdown_throws_exception_on_empty_data()
    {
        $this->expectException(MissingMarketDataException::class);
        $this->service->maximumDrawdown(
            $this->fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2021-05-01')
        );
    }

    public function test_maximum_drawdown_throws_exception_on_invalid_nav()
    {
        $this->createNavs([
            '2021-01-01' => 100.0,
            '2021-02-01' => 0.0, // Invalid NAV
            '2021-03-01' => 110.0,
        ]);

        $this->expectException(MissingMarketDataException::class);
        $this->service->maximumDrawdown(
            $this->fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2021-03-01')
        );
    }

    public function test_range_metric_start_date_resolution_uses_latest_available_nav()
    {
        $this->createNavs([
            '2021-02-01' => 100.0, // Friday
            '2021-02-04' => 90.0,  // Monday (Down)
            '2021-02-05' => 80.0,  // Tuesday
        ]);

        $drawdown = $this->service->maximumDrawdown(
            $this->fund,
            Carbon::parse('2021-02-03'), // Requesting from Sunday
            Carbon::parse('2021-02-05')
        );

        // Resolves to 2021-02-01 (100.0). Drops to 80.0 = -20%.
        $this->assertEqualsWithDelta(-0.2, $drawdown, 0.0001);
    }

    public function test_period_return_validation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->periodReturn(
            $this->fund,
            Carbon::parse('2021-01-05'),
            Carbon::parse('2021-01-01')
        );
    }

    public function test_cagr_validation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->cagr(
            $this->fund,
            Carbon::parse('2021-01-05'),
            Carbon::parse('2021-01-05')
        );
    }

    public function test_rolling_returns_validation_invalid_window()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->rollingReturns(
            $this->fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2021-04-01'),
            0
        );
    }

    public function test_rolling_returns_validation_invalid_dates()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->rollingReturns(
            $this->fund,
            Carbon::parse('2021-04-01'),
            Carbon::parse('2021-01-01'),
            12
        );
    }
}
