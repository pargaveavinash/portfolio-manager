<?php

namespace Tests\Feature\Models;

use App\Models\RiskFreeRate;
use App\Models\RiskFreeRateValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class RiskFreeRateValueTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_risk_free_rate_value(): void
    {
        $rate = RiskFreeRate::create([
            'name' => '91-Day Treasury Bill',
            'code' => 'TBILL_91D',
            'source' => 'FBIL',
        ]);

        $value = RiskFreeRateValue::create([
            'risk_free_rate_id' => $rate->id,
            'valuation_date' => '2026-01-01',
            'rate' => 0.065000,
        ]);

        $this->assertNotNull($value->id);
        $this->assertEquals('2026-01-01', $value->valuation_date->format('Y-m-d'));
        $this->assertEquals(0.065000, $value->rate);
    }

    public function test_belongs_to_risk_free_rate(): void
    {
        $rate = RiskFreeRate::create([
            'name' => '91-Day Treasury Bill',
            'code' => 'TBILL_91D',
            'source' => 'FBIL',
        ]);

        $value = RiskFreeRateValue::create([
            'risk_free_rate_id' => $rate->id,
            'valuation_date' => '2026-01-01',
            'rate' => 0.065000,
        ]);

        $this->assertInstanceOf(RiskFreeRate::class, $value->riskFreeRate);
        $this->assertEquals('TBILL_91D', $value->riskFreeRate->code);
    }

    public function test_cannot_create_duplicate_valuation_date_for_same_rate(): void
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

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('UNIQUE constraint failed'); // SQLite expectation

        RiskFreeRateValue::create([
            'risk_free_rate_id' => $rate->id,
            'valuation_date' => '2026-01-01',
            'rate' => 0.065500,
        ]);
    }

    public function test_rejects_negative_risk_free_rate(): void
    {
        $rate = RiskFreeRate::create([
            'name' => '91-Day Treasury Bill',
            'code' => 'TBILL_91D',
            'source' => 'FBIL',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate cannot be negative.');

        RiskFreeRateValue::create([
            'risk_free_rate_id' => $rate->id,
            'valuation_date' => '2026-01-02',
            'rate' => -0.010000,
        ]);
    }
}
