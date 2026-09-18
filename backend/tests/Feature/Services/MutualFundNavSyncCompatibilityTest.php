<?php

namespace Tests\Feature\Services;

use App\Models\MutualFund;
use App\Models\Benchmark;
use App\Services\MarketData\MutualFundNavSyncService;
use App\Contracts\MarketData\MutualFundDataProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MutualFundNavSyncCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_nav_sync_does_not_overwrite_managed_metadata_fields()
    {
        // 1. Create a benchmark
        $benchmark = Benchmark::create([
            'name' => 'NIFTY 50',
            'code' => 'NIFTY_50',
            'variant' => 'TRI',
        ]);

        // 2. Create a mutual fund with all metadata fields set
        $fund = MutualFund::factory()->create([
            'amfi_code' => '123456',
            'isin' => 'INF123456789',
            'amc_name' => 'Test AMC',
            'scheme_name' => 'Test Scheme',
            'plan_type' => 'DIRECT',
            'option_type' => 'GROWTH',
            'category' => 'Equity',
            'benchmark_id' => $benchmark->id,
            'sub_category' => 'Equity - Mid Cap',
            'inception_date' => '2015-01-01',
            'ter' => 1.2500,
            'aum' => 1500.50,
            'exit_load' => '1% before 1 year',
        ]);

        // 3. Mock the provider to return data for this fund
        $mockProvider = $this->createMock(MutualFundDataProviderInterface::class);
        $mockProvider->method('fetchLatestNavs')->willReturn([
            [
                'amfi_code' => '123456',
                'isin' => 'INF123456789',
                'amc_name' => 'Test AMC Updated', // Allow existing fields to update
                'scheme_name' => 'Test Scheme',
                'plan_type' => 'DIRECT',
                'option_type' => 'GROWTH',
                'category' => 'Equity',
                'nav_date' => '2026-01-02',
                'nav' => 150.55,
            ]
        ]);

        // 4. Run the sync service
        $service = new MutualFundNavSyncService($mockProvider);
        $service->sync();

        // 5. Verify the metadata fields are untouched, but existing AMFI fields are updated
        $fund->refresh();

        $this->assertEquals('Test AMC Updated', $fund->amc_name); // Normal field was updated

        // Metadata fields must remain intact (not overwritten by null or default)
        $this->assertEquals($benchmark->id, $fund->benchmark_id);
        $this->assertEquals('Equity - Mid Cap', $fund->sub_category);
        $this->assertEquals('2015-01-01', $fund->inception_date->format('Y-m-d'));
        $this->assertEquals(1.2500, $fund->ter);
        $this->assertEquals(1500.50, $fund->aum);
        $this->assertEquals('1% before 1 year', $fund->exit_load);
    }
}
