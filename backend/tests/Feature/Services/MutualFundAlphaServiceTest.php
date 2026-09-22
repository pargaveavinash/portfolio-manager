<?php

namespace Tests\Feature\Services;

use App\Domain\RateResolutionPolicy;
use App\Exceptions\MissingMarketDataException;
use App\Models\Benchmark;
use App\Models\MutualFund;
use App\Models\RiskFreeRate;
use App\Services\MutualFundAlphaService;
use App\Services\MutualFundBenchmarkService;
use App\Services\MutualFundPerformanceService;
use App\Services\RiskFreeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class MutualFundAlphaServiceTest extends TestCase
{
    use RefreshDatabase;

    private MutualFundAlphaService $service;
    private $performanceMock;
    private $benchmarkMock;
    private $riskFreeMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->performanceMock = Mockery::mock(MutualFundPerformanceService::class);
        $this->benchmarkMock = Mockery::mock(MutualFundBenchmarkService::class);
        $this->riskFreeMock = Mockery::mock(RiskFreeRateService::class);

        // Will fail because MutualFundAlphaService doesn't exist
        $this->service = new MutualFundAlphaService(
            $this->performanceMock,
            $this->benchmarkMock,
            $this->riskFreeMock
        );
    }

    public function test_it_calculates_positive_alpha_with_non_zero_beta()
    {
        // Fund CAGR = 12% (0.12)
        // Benchmark CAGR = 10% (0.10)
        // Risk-Free CAGR = 5% (0.05)
        // Beta = 1.2
        // Expected Return = 0.05 + 1.2 * (0.10 - 0.05) = 0.05 + 1.2 * 0.05 = 0.05 + 0.06 = 0.11 (11%)
        // Jensen's Alpha = 0.12 - 0.11 = 0.01 (1%)

        $fund = MutualFund::factory()->create();
        $benchmark = Benchmark::create(['code' => 'TEST', 'name' => 'Test', 'variant' => 'Total Return']);
        $riskFreeRate = RiskFreeRate::create(['code' => 'TEST', 'name' => 'Test', 'source' => 'Reserve Bank']);
        $policy = RateResolutionPolicy::strict();
        $start = Carbon::parse('2020-01-01');
        $end = Carbon::parse('2023-01-01');

        $this->performanceMock->shouldReceive('cagr')
            ->once()
            ->with($fund, $start, $end)
            ->andReturn(0.12);

        $this->benchmarkMock->shouldReceive('cagr')
            ->once()
            ->with($benchmark, $start, $end)
            ->andReturn(0.10);

        $this->riskFreeMock->shouldReceive('cagr')
            ->once()
            ->with($riskFreeRate, $start, $end, $policy, RiskFreeRateService::CONVENTION_EFFECTIVE_ANNUAL_365)
            ->andReturn(0.05);

        $this->benchmarkMock->shouldReceive('beta')
            ->once()
            ->with($fund, $benchmark, $start, $end)
            ->andReturn(1.2);

        $alpha = $this->service->calculateAlpha($fund, $benchmark, $riskFreeRate, $policy, $start, $end);

        $this->assertEquals(0.01, round($alpha, 4));
    }

    public function test_it_calculates_negative_alpha()
    {
        // Fund CAGR = 8% (0.08)
        // Benchmark CAGR = 10% (0.10)
        // Risk-Free CAGR = 5% (0.05)
        // Beta = 1.0
        // Expected Return = 0.05 + 1.0 * (0.10 - 0.05) = 0.10 (10%)
        // Jensen's Alpha = 0.08 - 0.10 = -0.02 (-2%)

        $fund = MutualFund::factory()->create();
        $benchmark = Benchmark::create(['code' => 'TEST', 'name' => 'Test', 'variant' => 'Total Return']);
        $riskFreeRate = RiskFreeRate::create(['code' => 'TEST', 'name' => 'Test', 'source' => 'Reserve Bank']);
        $policy = RateResolutionPolicy::strict();
        $start = Carbon::parse('2020-01-01');
        $end = Carbon::parse('2023-01-01');

        $this->performanceMock->shouldReceive('cagr')->andReturn(0.08);
        $this->benchmarkMock->shouldReceive('cagr')->andReturn(0.10);
        $this->riskFreeMock->shouldReceive('cagr')->andReturn(0.05);
        $this->benchmarkMock->shouldReceive('beta')->andReturn(1.0);

        $alpha = $this->service->calculateAlpha($fund, $benchmark, $riskFreeRate, $policy, $start, $end);

        $this->assertEquals(-0.02, round($alpha, 4));
    }

    public function test_it_handles_zero_beta()
    {
        // Fund CAGR = 6% (0.06)
        // Benchmark CAGR = 10% (0.10)
        // Risk-Free CAGR = 5% (0.05)
        // Beta = 0.0
        // Expected Return = 0.05 + 0.0 * (0.10 - 0.05) = 0.05 (5%)
        // Jensen's Alpha = 0.06 - 0.05 = 0.01 (1%)

        $fund = MutualFund::factory()->create();
        $benchmark = Benchmark::create(['code' => 'TEST', 'name' => 'Test', 'variant' => 'Total Return']);
        $riskFreeRate = RiskFreeRate::create(['code' => 'TEST', 'name' => 'Test', 'source' => 'Reserve Bank']);
        $policy = RateResolutionPolicy::strict();
        $start = Carbon::parse('2020-01-01');
        $end = Carbon::parse('2023-01-01');

        $this->performanceMock->shouldReceive('cagr')->andReturn(0.06);
        $this->benchmarkMock->shouldReceive('cagr')->andReturn(0.10);
        $this->riskFreeMock->shouldReceive('cagr')->andReturn(0.05);
        $this->benchmarkMock->shouldReceive('beta')->andReturn(0.0);

        $alpha = $this->service->calculateAlpha($fund, $benchmark, $riskFreeRate, $policy, $start, $end);

        $this->assertEquals(0.01, round($alpha, 4));
    }

    public function test_missing_fund_performance_bubbles_missing_market_data_exception()
    {
        $fund = MutualFund::factory()->create();
        $benchmark = Benchmark::create(['code' => 'TEST', 'name' => 'Test', 'variant' => 'Total Return']);
        $riskFreeRate = RiskFreeRate::create(['code' => 'TEST', 'name' => 'Test', 'source' => 'Reserve Bank']);
        $policy = RateResolutionPolicy::strict();
        $start = Carbon::parse('2020-01-01');
        $end = Carbon::parse('2023-01-01');

        $this->performanceMock->shouldReceive('cagr')->andThrow(new MissingMarketDataException('Fund data missing'));

        $this->expectException(MissingMarketDataException::class);
        $this->expectExceptionMessage('Fund data missing');

        $this->service->calculateAlpha($fund, $benchmark, $riskFreeRate, $policy, $start, $end);
    }

    public function test_missing_benchmark_data_bubbles_missing_market_data_exception()
    {
        $fund = MutualFund::factory()->create();
        $benchmark = Benchmark::create(['code' => 'TEST', 'name' => 'Test', 'variant' => 'Total Return']);
        $riskFreeRate = RiskFreeRate::create(['code' => 'TEST', 'name' => 'Test', 'source' => 'Reserve Bank']);
        $policy = RateResolutionPolicy::strict();
        $start = Carbon::parse('2020-01-01');
        $end = Carbon::parse('2023-01-01');

        $this->performanceMock->shouldReceive('cagr')->andReturn(0.12);
        $this->benchmarkMock->shouldReceive('cagr')->andThrow(new MissingMarketDataException('Benchmark data missing'));

        $this->expectException(MissingMarketDataException::class);
        $this->expectExceptionMessage('Benchmark data missing');

        $this->service->calculateAlpha($fund, $benchmark, $riskFreeRate, $policy, $start, $end);
    }

    public function test_missing_risk_free_data_bubbles_missing_market_data_exception()
    {
        $fund = MutualFund::factory()->create();
        $benchmark = Benchmark::create(['code' => 'TEST', 'name' => 'Test', 'variant' => 'Total Return']);
        $riskFreeRate = RiskFreeRate::create(['code' => 'TEST', 'name' => 'Test', 'source' => 'Reserve Bank']);
        $policy = RateResolutionPolicy::strict();
        $start = Carbon::parse('2020-01-01');
        $end = Carbon::parse('2023-01-01');

        $this->performanceMock->shouldReceive('cagr')->andReturn(0.12);
        $this->benchmarkMock->shouldReceive('cagr')->andReturn(0.10);
        $this->riskFreeMock->shouldReceive('cagr')->andThrow(new MissingMarketDataException('Risk free data missing'));

        $this->expectException(MissingMarketDataException::class);
        $this->expectExceptionMessage('Risk free data missing');

        $this->service->calculateAlpha($fund, $benchmark, $riskFreeRate, $policy, $start, $end);
    }
}
