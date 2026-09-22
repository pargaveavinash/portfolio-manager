<?php

namespace Tests\Feature\Services;

use App\Domain\Evaluation\EvaluationProfile;
use App\Domain\Evaluation\EvaluationProfileResolver;
use App\Domain\Evaluation\EvaluationResult;
use App\Domain\Evaluation\MetricResult;
use App\Models\MutualFund;
use App\Models\MutualFundNav;
use App\Services\MutualFundEvaluationService;
use App\Services\MutualFundPerformanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class MutualFundEvaluationServiceTest extends TestCase
{
    use RefreshDatabase;

    private MutualFundEvaluationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $alphaMock = Mockery::mock(\App\Services\MutualFundAlphaService::class);
        $alphaMock->shouldReceive('calculateAlpha')->andReturn(0.015)->byDefault();

        $this->service = new MutualFundEvaluationService(
            new EvaluationProfileResolver(),
            new MutualFundPerformanceService(),
            $alphaMock
        );
    }

    private function createNavs(MutualFund $fund, array $navData): void
    {
        foreach ($navData as $date => $nav) {
            MutualFundNav::create([
                'mutual_fund_id' => $fund->id,
                'nav_date' => $date,
                'nav' => $nav,
            ]);
        }
    }

    public function test_evaluation_result_contract_preserves_inputs_and_exposes_metrics()
    {
        $fund = MutualFund::factory()->create();
        $metrics = [
            new MetricResult('cagr', MetricResult::STATUS_AVAILABLE, 0.15)
        ];

        // This should fail because EvaluationResult does not exist
        $result = new EvaluationResult($fund, 'Equity', $metrics);

        $this->assertSame($fund, $result->getMutualFund());
        $this->assertEquals('Equity', $result->getFamily());
        $this->assertSame($metrics, $result->getMetrics());
    }

    public function test_service_evaluates_equity_profile_with_available_metrics()
    {
        $benchmark = \App\Models\Benchmark::create(['code' => 'TEST', 'name' => 'Test', 'variant' => 'Total Return']);
        $fund = MutualFund::factory()->create([
            'category' => 'Equity',
            'sub_category' => 'Large Cap',
            'benchmark_id' => $benchmark->id,
        ]);

        $start = Carbon::parse('2020-01-01');
        $navs = [];
        // Supply enough data for rolling_returns and others
        for ($i = 0; $i <= 36; $i++) {
            $date = $start->copy()->addMonthsNoOverflow($i);
            $navs[$date->format('Y-m-d')] = 100.0 + ($i * 5);
        }
        $this->createNavs($fund, $navs);

        $rate = \App\Models\RiskFreeRate::create(['code' => 'TEST', 'name' => 'Test', 'source' => 'Reserve Bank']);
        $policy = \App\Domain\RateResolutionPolicy::strict();

        $result = $this->service->evaluate(
            $fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2023-01-01'),
            $rate,
            $policy
        );

        $this->assertInstanceOf(EvaluationResult::class, $result);
        $this->assertEquals('Equity', $result->getFamily());

        $metrics = $result->getMetrics();
        $this->assertCount(6, $metrics);

        $metricNames = array_map(fn(MetricResult $m) => $m->name, $metrics);
        $this->assertEqualsCanonicalizing([
            'period_return',
            'cagr',
            'rolling_returns',
            'volatility',
            'maximum_drawdown',
            'jensens_alpha'
        ], $metricNames);

        foreach ($metrics as $metric) {
            $this->assertEquals(MetricResult::STATUS_AVAILABLE, $metric->status, "Metric {$metric->name} should be AVAILABLE");
            $this->assertNotNull($metric->value);

            if ($metric->name === 'cagr') {
                $this->assertIsFloat($metric->value);
            }

            if ($metric->name === 'rolling_returns') {
                $this->assertIsArray($metric->value);
                $this->assertNotEmpty($metric->value);
                $this->assertArrayHasKey('date', $metric->value[0]);
                $this->assertArrayHasKey('return', $metric->value[0]);
            }
        }
    }

    public function test_service_evaluates_debt_profile_metrics()
    {
        $fund = MutualFund::factory()->create([
            'category' => 'Debt',
            'sub_category' => 'Liquid Fund',
        ]);

        $this->createNavs($fund, [
            '2021-01-01' => 100.0,
            '2022-01-01' => 105.0,
            '2023-01-01' => 110.0,
        ]);

        $result = $this->service->evaluate(
            $fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2023-01-01')
        );

        $this->assertEquals('Debt', $result->getFamily());
        $metrics = $result->getMetrics();
        $metricNames = array_map(fn(MetricResult $m) => $m->name, $metrics);

        $this->assertEqualsCanonicalizing([
            'cagr',
            'volatility',
            'maximum_drawdown'
        ], $metricNames);
    }

    public function test_service_evaluates_hybrid_profile_metrics()
    {
        $benchmark = \App\Models\Benchmark::create(['code' => 'TEST', 'name' => 'Test', 'variant' => 'Total Return']);
        $fund = MutualFund::factory()->create([
            'category' => 'Hybrid',
            'sub_category' => 'Aggressive Hybrid',
            'benchmark_id' => $benchmark->id,
        ]);

        $this->createNavs($fund, [
            '2021-01-01' => 100.0,
            '2022-01-01' => 115.0,
            '2023-01-01' => 125.0,
        ]);

        $rate = \App\Models\RiskFreeRate::create(['code' => 'TEST', 'name' => 'Test', 'source' => 'Reserve Bank']);
        $policy = \App\Domain\RateResolutionPolicy::strict();

        $result = $this->service->evaluate(
            $fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2023-01-01'),
            $rate,
            $policy
        );

        $this->assertEquals('Hybrid', $result->getFamily());
        $metricNames = array_map(fn(MetricResult $m) => $m->name, $result->getMetrics());

        $this->assertEqualsCanonicalizing([
            'cagr',
            'rolling_returns',
            'volatility',
            'maximum_drawdown',
            'jensens_alpha'
        ], $metricNames);
    }

    public function test_service_evaluates_passive_profile_metrics_with_unavailable_data()
    {
        $fund = MutualFund::factory()->create([
            'category' => 'Equity',
            'sub_category' => 'Index Funds/ETFs', // Resolves to Passive profile
        ]);

        $this->createNavs($fund, [
            '2021-01-01' => 100.0,
            '2023-01-01' => 125.0,
        ]);

        $result = $this->service->evaluate(
            $fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2023-01-01')
        );

        $this->assertEquals('Passive', $result->getFamily());
        $metrics = $result->getMetrics();

        $cagr = collect($metrics)->firstWhere('name', 'cagr');
        $benchmark = collect($metrics)->firstWhere('name', 'benchmark_comparison');
        $ter = collect($metrics)->firstWhere('name', 'ter');

        $this->assertEquals(MetricResult::STATUS_AVAILABLE, $cagr->status);
        $this->assertNotNull($cagr->value);

        $this->assertEquals(MetricResult::STATUS_DATA_UNAVAILABLE, $benchmark->status);
        $this->assertNull($benchmark->value);
        $this->assertNotNull($benchmark->reason);

        $this->assertEquals(MetricResult::STATUS_DATA_UNAVAILABLE, $ter->status);
        $this->assertNull($ter->value);
        $this->assertNotNull($ter->reason);
    }

    public function test_insufficient_data_for_some_metrics_does_not_fail_entire_evaluation()
    {
        $benchmark = \App\Models\Benchmark::create(['code' => 'TEST', 'name' => 'Test', 'variant' => 'Total Return']);
        $fund = MutualFund::factory()->create([
            'category' => 'Equity',
            'sub_category' => 'Large Cap',
            'benchmark_id' => $benchmark->id,
        ]);

        $this->createNavs($fund, [
            '2022-01-01' => 100.0,
            '2022-06-01' => 110.0,
        ]);

        $rate = \App\Models\RiskFreeRate::create(['code' => 'TEST', 'name' => 'Test', 'source' => 'Reserve Bank']);
        $policy = \App\Domain\RateResolutionPolicy::strict();

        $result = $this->service->evaluate(
            $fund,
            Carbon::parse('2022-01-01'),
            Carbon::parse('2022-06-01'),
            $rate,
            $policy
        );

        $metrics = collect($result->getMetrics());
        $rollingReturns = $metrics->firstWhere('name', 'rolling_returns');

        $this->assertCount(6, $metrics);

        $this->assertEquals(MetricResult::STATUS_INSUFFICIENT_DATA, $rollingReturns->status);
        $this->assertNull($rollingReturns->value);
        $this->assertNotNull($rollingReturns->reason);
    }

    public function test_insufficient_data_preserves_null_value_and_does_not_fabricate_zero()
    {
        $fund = MutualFund::factory()->create(['category' => 'Debt']);

        $result = $this->service->evaluate(
            $fund,
            Carbon::parse('2020-01-01'),
            Carbon::parse('2023-01-01')
        );

        $cagr = collect($result->getMetrics())->firstWhere('name', 'cagr');

        $this->assertEquals(MetricResult::STATUS_INSUFFICIENT_DATA, $cagr->status);
        $this->assertNull($cagr->value); // Must be explicitly null, not 0
        $this->assertNotNull($cagr->reason);
    }

    public function test_no_applicable_metric_should_be_silently_omitted()
    {
        $benchmark = \App\Models\Benchmark::create(['code' => 'TEST', 'name' => 'Test', 'variant' => 'Total Return']);
        $fund = MutualFund::factory()->create(['category' => 'Equity', 'benchmark_id' => $benchmark->id]);

        $rate = \App\Models\RiskFreeRate::create(['code' => 'TEST', 'name' => 'Test', 'source' => 'Reserve Bank']);
        $policy = \App\Domain\RateResolutionPolicy::strict();

        $result = $this->service->evaluate(
            $fund,
            Carbon::parse('2020-01-01'),
            Carbon::parse('2023-01-01'),
            $rate,
            $policy
        );

        $metricNames = array_map(fn($m) => $m->name, $result->getMetrics());

        $this->assertEqualsCanonicalizing([
            'period_return',
            'cagr',
            'rolling_returns',
            'volatility',
            'maximum_drawdown',
            'jensens_alpha'
        ], $metricNames);
    }

    public function test_service_delegates_to_evaluation_profile_resolver()
    {
        $fund = MutualFund::factory()->create();

        $profile = new EvaluationProfile('CustomDelegatedProfile', ['cagr', 'custom_metric']);

        $resolverMock = Mockery::mock(EvaluationProfileResolver::class);
        $resolverMock->shouldReceive('resolve')
            ->once()
            ->with($fund)
            ->andReturn($profile);

        $performanceMock = Mockery::mock(MutualFundPerformanceService::class);
        // Using Mockery::any() because the exact method name might be dynamic or generic in this test,
        // but 'cagr' should at least be called.
        // We will just allow any calls here, since we test performance delegation in the next test.
        $performanceMock->shouldReceive('cagr')->andReturn(0.12);

        $alphaMock = Mockery::mock(\App\Services\MutualFundAlphaService::class);
        $service = new MutualFundEvaluationService($resolverMock, $performanceMock, $alphaMock);

        $result = $service->evaluate($fund, Carbon::parse('2021-01-01'), Carbon::parse('2023-01-01'));

        // Assert that the family name comes exactly from our mocked resolver
        $this->assertEquals('CustomDelegatedProfile', $result->getFamily());

        $metricNames = array_map(fn(MetricResult $m) => $m->name, $result->getMetrics());

        $this->assertEqualsCanonicalizing(['cagr', 'custom_metric'], $metricNames);
    }

    public function test_service_delegates_to_performance_service_for_calculations()
    {
        $fund = MutualFund::factory()->create();

        $profile = new EvaluationProfile('PerformanceDelegationProfile', [
            'cagr',
            'volatility',
            'maximum_drawdown',
            'rolling_returns'
        ]);

        $resolverMock = Mockery::mock(EvaluationProfileResolver::class);
        $resolverMock->shouldReceive('resolve')->andReturn($profile);

        $performanceMock = Mockery::mock(MutualFundPerformanceService::class);

        $start = Carbon::parse('2020-01-01');
        $end = Carbon::parse('2023-01-01');

        $performanceMock->shouldReceive('cagr')
            ->once()
            ->with($fund, Mockery::on(fn($d) => $d->eq($start)), Mockery::on(fn($d) => $d->eq($end)))
            ->andReturn(0.155);

        $performanceMock->shouldReceive('annualizedVolatility')
            ->once()
            ->with($fund, Mockery::on(fn($d) => $d->eq($start)), Mockery::on(fn($d) => $d->eq($end)))
            ->andReturn(0.123);

        $performanceMock->shouldReceive('maximumDrawdown')
            ->once()
            ->with($fund, Mockery::on(fn($d) => $d->eq($start)), Mockery::on(fn($d) => $d->eq($end)))
            ->andReturn(-0.250);

        $mockRollingReturns = [
            ['date' => '2022-01-01', 'return' => 0.08],
            ['date' => '2022-02-01', 'return' => 0.10],
        ];

        $performanceMock->shouldReceive('rollingReturns')
            ->once()
            ->with($fund, Mockery::on(fn($d) => $d->eq($start)), Mockery::on(fn($d) => $d->eq($end)), 12)
            ->andReturn($mockRollingReturns);

        $alphaMock = Mockery::mock(\App\Services\MutualFundAlphaService::class);
        $service = new MutualFundEvaluationService($resolverMock, $performanceMock, $alphaMock);

        $result = $service->evaluate($fund, $start, $end);

        $metrics = collect($result->getMetrics());

        $this->assertEquals(0.155, $metrics->firstWhere('name', 'cagr')->value);
        $this->assertEquals(0.123, $metrics->firstWhere('name', 'volatility')->value);
        $this->assertEquals(-0.250, $metrics->firstWhere('name', 'maximum_drawdown')->value);
        $this->assertEquals($mockRollingReturns, $metrics->firstWhere('name', 'rolling_returns')->value);
    }

    public function test_missing_risk_free_rate_when_alpha_required_throws_exception()
    {
        $fund = MutualFund::factory()->create([
            'category' => 'Equity',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("RiskFreeRate and RateResolutionPolicy must be provided to calculate Jensen's Alpha.");

        $this->service->evaluate(
            $fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2023-01-01'),
            null, // RiskFreeRate is missing
            \App\Domain\RateResolutionPolicy::strict()
        );
    }

    public function test_missing_rate_resolution_policy_when_alpha_required_throws_exception()
    {
        $fund = MutualFund::factory()->create([
            'category' => 'Equity',
        ]);

        $rate = \App\Models\RiskFreeRate::create(['code' => 'TEST', 'name' => 'Test', 'source' => 'Reserve Bank']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("RiskFreeRate and RateResolutionPolicy must be provided to calculate Jensen's Alpha.");

        $this->service->evaluate(
            $fund,
            Carbon::parse('2021-01-01'),
            Carbon::parse('2023-01-01'),
            $rate,
            null // Policy is missing
        );
    }

    public function test_evaluation_dispatches_jensens_alpha_when_present()
    {
        // We inject a mock alpha service to ensure dispatch happens.
        // Wait, MutualFundEvaluationService constructor currently doesn't accept AlphaService.
        // This will fail (RED phase).
        $profile = new EvaluationProfile('AlphaProfile', ['jensens_alpha']);

        $resolverMock = Mockery::mock(EvaluationProfileResolver::class);
        $resolverMock->shouldReceive('resolve')->andReturn($profile);

        $performanceMock = Mockery::mock(MutualFundPerformanceService::class);
        $alphaMock = Mockery::mock(\App\Services\MutualFundAlphaService::class);

        $benchmark = \App\Models\Benchmark::create(['code' => 'TEST', 'name' => 'Test', 'variant' => 'Total Return']);
        $fund = MutualFund::factory()->create(['benchmark_id' => $benchmark->id]);
        $rate = \App\Models\RiskFreeRate::create(['code' => 'TEST', 'name' => 'Test', 'source' => 'Reserve Bank']);
        $policy = \App\Domain\RateResolutionPolicy::strict();

        $start = Carbon::parse('2020-01-01');
        $end = Carbon::parse('2023-01-01');

        $alphaMock->shouldReceive('calculateAlpha')
            ->once()
            ->with($fund, $fund->benchmark, $rate, $policy, Mockery::on(fn($d) => $d->eq($start)), Mockery::on(fn($d) => $d->eq($end)))
            ->andReturn(0.015);

        // This instantiation fails currently, leading to RED
        $service = new MutualFundEvaluationService($resolverMock, $performanceMock, $alphaMock);

        $result = $service->evaluate($fund, $start, $end, $rate, $policy);

        $metrics = collect($result->getMetrics());
        $alphaMetric = $metrics->firstWhere('name', 'jensens_alpha');

        $this->assertEquals(MetricResult::STATUS_AVAILABLE, $alphaMetric->status);
        $this->assertEquals(0.015, $alphaMetric->value);
    }

    public function test_missing_market_data_from_alpha_maps_to_insufficient_data_status()
    {
        $profile = new EvaluationProfile('AlphaProfile', ['jensens_alpha']);

        $resolverMock = Mockery::mock(EvaluationProfileResolver::class);
        $resolverMock->shouldReceive('resolve')->andReturn($profile);

        $performanceMock = Mockery::mock(MutualFundPerformanceService::class);
        $alphaMock = Mockery::mock(\App\Services\MutualFundAlphaService::class);

        $benchmark = \App\Models\Benchmark::create(['code' => 'TEST', 'name' => 'Test', 'variant' => 'Total Return']);
        $fund = MutualFund::factory()->create(['benchmark_id' => $benchmark->id]);
        $rate = \App\Models\RiskFreeRate::create(['code' => 'TEST', 'name' => 'Test', 'source' => 'Reserve Bank']);
        $policy = \App\Domain\RateResolutionPolicy::strict();

        $start = Carbon::parse('2020-01-01');
        $end = Carbon::parse('2023-01-01');

        $alphaMock->shouldReceive('calculateAlpha')
            ->once()
            ->andThrow(new \App\Exceptions\MissingMarketDataException('No risk free rate'));

        // Fails in RED
        $service = new MutualFundEvaluationService($resolverMock, $performanceMock, $alphaMock);

        $result = $service->evaluate($fund, $start, $end, $rate, $policy);

        $metrics = collect($result->getMetrics());
        $alphaMetric = $metrics->firstWhere('name', 'jensens_alpha');

        $this->assertEquals(MetricResult::STATUS_INSUFFICIENT_DATA, $alphaMetric->status);
        $this->assertEquals('No risk free rate', $alphaMetric->reason);
        $this->assertNull($alphaMetric->value);
    }
}
