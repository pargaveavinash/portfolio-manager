<?php

namespace Tests\Feature\Models;

use App\Models\MutualFund;
use App\Models\Benchmark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class MutualFundMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_nullable_metadata_fields_work()
    {
        $fund = MutualFund::factory()->create([
            'benchmark_id' => null,
            'sub_category' => 'Equity - Mid Cap',
            'inception_date' => '2015-01-01',
            'ter' => 1.2500,
            'aum' => 1500.50,
            'exit_load' => '1% before 1 year',
        ]);

        $this->assertDatabaseHas('mutual_funds', [
            'id' => $fund->id,
            'sub_category' => 'Equity - Mid Cap',
            'inception_date' => '2015-01-01 00:00:00',
            'ter' => 1.2500,
            'aum' => 1500.50,
            'exit_load' => '1% before 1 year',
        ]);

        $fund->refresh();
        $this->assertEquals('Equity - Mid Cap', $fund->sub_category);
        $this->assertEquals('2015-01-01', $fund->inception_date->format('Y-m-d'));
        $this->assertEquals(1.2500, $fund->ter);
        $this->assertEquals(1500.50, $fund->aum);
        $this->assertEquals('1% before 1 year', $fund->exit_load);
    }

    public function test_benchmark_relationship_works()
    {
        $benchmark = Benchmark::create([
            'name' => 'NIFTY 50',
            'code' => 'NIFTY_50',
            'variant' => 'TRI',
        ]);

        $fund = MutualFund::factory()->create([
            'benchmark_id' => $benchmark->id,
        ]);

        $this->assertInstanceOf(Benchmark::class, $fund->benchmark);
        $this->assertEquals($benchmark->id, $fund->benchmark->id);
    }

    public function test_invalid_benchmark_id_cannot_be_persisted()
    {
        $this->expectException(QueryException::class);

        MutualFund::factory()->create([
            'benchmark_id' => 999999, // Non-existent
        ]);
    }
}
