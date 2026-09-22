<?php

namespace Tests\Feature\Models;

use App\Models\RiskFreeRate;
use App\Models\RiskFreeRateValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class RiskFreeRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_risk_free_rate(): void
    {
        $rate = RiskFreeRate::create([
            'name' => '91-Day Treasury Bill',
            'code' => 'TBILL_91D',
            'source' => 'FBIL',
        ]);

        $this->assertNotNull($rate->id);
        $this->assertEquals('91-Day Treasury Bill', $rate->name);
        $this->assertEquals('TBILL_91D', $rate->code);
    }

    public function test_has_risk_free_rate_values_relationship(): void
    {
        $rate = RiskFreeRate::create([
            'name' => '91-Day Treasury Bill',
            'code' => 'TBILL_91D',
            'source' => 'FBIL',
        ]);

        RiskFreeRateValue::create([
            'risk_free_rate_id' => $rate->id,
            'valuation_date' => '2026-01-01',
            'rate' => 0.065000,
        ]);

        $this->assertCount(1, $rate->values);
        $this->assertInstanceOf(RiskFreeRateValue::class, $rate->values->first());
    }
}
