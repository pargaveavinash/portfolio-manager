<?php

namespace Tests\Feature\Models;

use App\Models\Benchmark;
use App\Models\BenchmarkValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class BenchmarkValueTest extends TestCase
{
    use RefreshDatabase;

    protected $benchmark;

    protected function setUp(): void
    {
        parent::setUp();

        $this->benchmark = Benchmark::create([
            'name' => 'NIFTY 50',
            'code' => 'NIFTY_50',
            'variant' => 'TRI',
        ]);
    }

    public function test_benchmark_value_can_be_created()
    {
        $value = BenchmarkValue::create([
            'benchmark_id' => $this->benchmark->id,
            'value' => 15000.123456,
            'valuation_date' => '2026-01-01',
        ]);

        $this->assertDatabaseHas('benchmark_values', [
            'benchmark_id' => $this->benchmark->id,
            'value' => 15000.123456,
            'valuation_date' => '2026-01-01 00:00:00',
        ]);

        $this->assertEquals(15000.123456, $value->value);
        $this->assertEquals('2026-01-01', $value->valuation_date->format('Y-m-d'));
    }

    public function test_benchmark_value_precision_supports_6_decimals()
    {
        $value = BenchmarkValue::create([
            'benchmark_id' => $this->benchmark->id,
            'value' => 15000.123456,
            'valuation_date' => '2026-01-01',
        ]);

        // Reload to test database cast/persistence
        $reloaded = BenchmarkValue::find($value->id);

        // Assert value is formatted as string when accessed or cast matches exact string format depending on implementation,
        // we'll just check float equality to what was inserted assuming cast is decimal or double, but DB must be 20,6
        $this->assertEquals(15000.123456, $reloaded->value);
    }

    public function test_duplicate_benchmark_id_and_valuation_date_is_rejected()
    {
        BenchmarkValue::create([
            'benchmark_id' => $this->benchmark->id,
            'value' => 15000.123456,
            'valuation_date' => '2026-01-01',
        ]);

        $this->expectException(QueryException::class);

        BenchmarkValue::create([
            'benchmark_id' => $this->benchmark->id,
            'value' => 15100.000000,
            'valuation_date' => '2026-01-01',
        ]);
    }

    public function test_benchmark_value_belongs_to_benchmark()
    {
        $value = BenchmarkValue::create([
            'benchmark_id' => $this->benchmark->id,
            'value' => 15000.123456,
            'valuation_date' => '2026-01-01',
        ]);

        $this->assertInstanceOf(Benchmark::class, $value->benchmark);
        $this->assertEquals($this->benchmark->id, $value->benchmark->id);
    }
}
