<?php

namespace Tests\Feature\Services;

use App\Domain\Evaluation\EvaluationProfileResolver;
use App\Domain\Evaluation\MetricResult;
use App\Models\MutualFund;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class MutualFundEvaluationProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function test_distinguishes_between_unavailable_and_zero_values()
    {
        $unavailableResult = new MetricResult(
            name: 'cagr_5y',
            status: 'INSUFFICIENT_DATA',
            value: null,
            reason: 'Fund has not existed for 5 years'
        );

        $zeroResult = new MetricResult(
            name: 'cagr_5y',
            status: 'AVAILABLE',
            value: 0.0,
            reason: null
        );

        $this->assertNull($unavailableResult->value);
        $this->assertEquals('INSUFFICIENT_DATA', $unavailableResult->status);
        
        $this->assertEquals(0.0, $zeroResult->value);
        $this->assertEquals('AVAILABLE', $zeroResult->status);
        
        $this->assertNotEquals($unavailableResult, $zeroResult);
    }

    /**
     * @test
     */
    public function test_evidence_state_available_has_value()
    {
        $result = new MetricResult(
            name: 'period_return',
            status: 'AVAILABLE',
            value: 12.5,
            reason: null
        );

        $this->assertEquals(12.5, $result->value);
        $this->assertEquals('AVAILABLE', $result->status);
        $this->assertTrue($result->isAvailable());
    }

    /**
     * @test
     */
    public function test_evidence_state_insufficient_data_has_null_value()
    {
        $result = new MetricResult(
            name: 'rolling_returns',
            status: 'INSUFFICIENT_DATA',
            value: null,
            reason: 'Not enough daily observations'
        );

        $this->assertNull($result->value);
        $this->assertEquals('INSUFFICIENT_DATA', $result->status);
        $this->assertFalse($result->isAvailable());
    }
    
    /**
     * @test
     */
    public function test_evidence_state_data_unavailable_has_null_value()
    {
        $result = new MetricResult(
            name: 'ter',
            status: 'DATA_UNAVAILABLE',
            value: null,
            reason: 'TER data not published by AMC'
        );

        $this->assertNull($result->value);
        $this->assertEquals('DATA_UNAVAILABLE', $result->status);
        $this->assertFalse($result->isAvailable());
    }

    /**
     * @test
     */
    public function test_evidence_state_not_applicable_has_null_value()
    {
        $result = new MetricResult(
            name: 'tracking_error',
            status: 'NOT_APPLICABLE',
            value: null,
            reason: 'Tracking error does not apply to active funds'
        );

        $this->assertNull($result->value);
        $this->assertEquals('NOT_APPLICABLE', $result->status);
        $this->assertFalse($result->isAvailable());
    }

    /**
     * @test
     */
    public function test_resolves_equity_profile_from_equity_category()
    {
        $fund = MutualFund::factory()->create(['category' => 'Equity', 'sub_category' => 'Large Cap Fund']);
        
        $resolver = new EvaluationProfileResolver();
        $profile = $resolver->resolve($fund);
        
        $this->assertEquals('Equity', $profile->getFamily());
        $metrics = $profile->getExpectedMetrics();
        
        $this->assertContains('period_return', $metrics);
        $this->assertContains('cagr', $metrics);
        $this->assertContains('rolling_returns', $metrics);
        $this->assertContains('volatility', $metrics);
        $this->assertContains('maximum_drawdown', $metrics);
    }

    /**
     * @test
     */
    public function test_resolves_debt_profile_from_debt_category()
    {
        $fund = MutualFund::factory()->create(['category' => 'Debt', 'sub_category' => 'Liquid Fund']);
        
        $resolver = new EvaluationProfileResolver();
        $profile = $resolver->resolve($fund);
        
        $this->assertEquals('Debt', $profile->getFamily());
        $metrics = $profile->getExpectedMetrics();
        
        $this->assertContains('cagr', $metrics);
        $this->assertContains('volatility', $metrics);
        $this->assertContains('maximum_drawdown', $metrics);
    }

    /**
     * @test
     */
    public function test_resolves_hybrid_profile_from_hybrid_category()
    {
        $fund = MutualFund::factory()->create(['category' => 'Hybrid', 'sub_category' => 'Aggressive Hybrid Fund']);
        
        $resolver = new EvaluationProfileResolver();
        $profile = $resolver->resolve($fund);
        
        $this->assertEquals('Hybrid', $profile->getFamily());
        $metrics = $profile->getExpectedMetrics();
        
        $this->assertContains('cagr', $metrics);
        $this->assertContains('rolling_returns', $metrics);
        $this->assertContains('volatility', $metrics);
        $this->assertContains('maximum_drawdown', $metrics);
    }

    /**
     * @test
     */
    public function test_resolves_passive_profile_from_index_sub_category()
    {
        $fund = MutualFund::factory()->create(['category' => 'Other', 'sub_category' => 'Index Funds/ETFs']);
        
        $resolver = new EvaluationProfileResolver();
        $profile = $resolver->resolve($fund);
        
        $this->assertEquals('Passive', $profile->getFamily());
        $metrics = $profile->getExpectedMetrics();
        
        $this->assertContains('cagr', $metrics);
        $this->assertContains('benchmark_comparison', $metrics);
        $this->assertContains('ter', $metrics);
    }

    /**
     * @test
     */
    public function test_rejects_unsupported_mutual_fund_category()
    {
        $fund = MutualFund::factory()->create(['category' => 'UnknownCategory', 'sub_category' => 'UnknownSubCategory']);
        
        $resolver = new EvaluationProfileResolver();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported mutual fund category');

        $resolver->resolve($fund);
    }
}
