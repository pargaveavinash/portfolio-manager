<?php

namespace Tests\Feature\Domain;

use App\Domain\RateResolutionPolicy;
use Tests\TestCase;

class RateResolutionPolicyTest extends TestCase
{
    public function test_can_create_strict_policy(): void
    {
        $policy = RateResolutionPolicy::strict();
        
        $this->assertEquals(0, $policy->getMaxLookbackDays());
        $this->assertFalse($policy->isFallbackAllowed());
        $this->assertNull($policy->getFallbackRate());
    }

    public function test_can_create_lookback_policy(): void
    {
        $policy = RateResolutionPolicy::lookback(5);
        
        $this->assertEquals(5, $policy->getMaxLookbackDays());
        $this->assertFalse($policy->isFallbackAllowed());
        $this->assertNull($policy->getFallbackRate());
    }

    public function test_can_create_fallback_policy(): void
    {
        $policy = RateResolutionPolicy::fallback(0.060000);
        
        $this->assertEquals(0, $policy->getMaxLookbackDays());
        $this->assertTrue($policy->isFallbackAllowed());
        $this->assertEquals(0.060000, $policy->getFallbackRate());
    }

    public function test_can_create_lookback_with_fallback_policy(): void
    {
        $policy = RateResolutionPolicy::lookbackWithFallback(3, 0.055000);
        
        $this->assertEquals(3, $policy->getMaxLookbackDays());
        $this->assertTrue($policy->isFallbackAllowed());
        $this->assertEquals(0.055000, $policy->getFallbackRate());
    }
}
