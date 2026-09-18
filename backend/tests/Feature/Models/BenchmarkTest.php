<?php

namespace Tests\Feature\Models;

use App\Models\Benchmark;
use App\Models\BenchmarkValue;
use App\Models\MutualFund;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class BenchmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_benchmark_can_be_created()
    {
        $benchmark = Benchmark::create([
            'name' => 'NIFTY 50',
            'code' => 'NIFTY_50',
            'variant' => 'TRI',
        ]);

        $this->assertDatabaseHas('benchmarks', [
            'name' => 'NIFTY 50',
            'code' => 'NIFTY_50',
            'variant' => 'TRI',
        ]);

        $this->assertEquals('NIFTY 50', $benchmark->name);
        $this->assertEquals('NIFTY_50', $benchmark->code);
        $this->assertEquals('TRI', $benchmark->variant);
    }

    public function test_benchmark_code_must_be_unique()
    {
        Benchmark::create([
            'name' => 'NIFTY 50',
            'code' => 'NIFTY_50',
            'variant' => 'TRI',
        ]);

        $this->expectException(QueryException::class);

        Benchmark::create([
            'name' => 'NIFTY 50 Another',
            'code' => 'NIFTY_50',
            'variant' => 'PRI',
        ]);
    }

    public function test_benchmark_has_many_mutual_funds()
    {
        $benchmark = Benchmark::create([
            'name' => 'NIFTY 50',
            'code' => 'NIFTY_50',
            'variant' => 'TRI',
        ]);

        $fund1 = MutualFund::factory()->create(['benchmark_id' => $benchmark->id]);
        $fund2 = MutualFund::factory()->create(['benchmark_id' => $benchmark->id]);

        $this->assertCount(2, $benchmark->mutualFunds);
        $this->assertTrue($benchmark->mutualFunds->contains($fund1));
        $this->assertTrue($benchmark->mutualFunds->contains($fund2));
    }

    public function test_benchmark_has_many_benchmark_values()
    {
        $benchmark = Benchmark::create([
            'name' => 'NIFTY 50',
            'code' => 'NIFTY_50',
            'variant' => 'TRI',
        ]);

        $value1 = BenchmarkValue::create([
            'benchmark_id' => $benchmark->id,
            'value' => 15000.123456,
            'valuation_date' => '2026-01-01',
        ]);

        $value2 = BenchmarkValue::create([
            'benchmark_id' => $benchmark->id,
            'value' => 15100.123456,
            'valuation_date' => '2026-01-02',
        ]);

        $this->assertCount(2, $benchmark->values);
        $this->assertTrue($benchmark->values->contains($value1));
        $this->assertTrue($benchmark->values->contains($value2));
    }
}
