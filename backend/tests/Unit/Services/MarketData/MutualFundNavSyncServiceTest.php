<?php

namespace Tests\Unit\Services\MarketData;

use App\Models\MutualFund;
use App\Models\MutualFundNav;
use App\Services\MarketData\MutualFundNavSyncService;
use App\Contracts\MarketData\MutualFundDataProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MutualFundNavSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_mutual_fund_master_and_nav_records()
    {
        $mockProvider = $this->createMock(MutualFundDataProviderInterface::class);
        $mockProvider->method('fetchLatestNavs')->willReturn([
            [
                'amfi_code'   => '120503',
                'isin'        => 'INF200K01170',
                'amc_name'    => 'SBI Mutual Fund',
                'scheme_name' => 'SBI Flexicap Fund - Direct Plan - Growth',
                'plan_type'   => 'DIRECT',
                'option_type' => 'GROWTH',
                'category'    => 'Equity',
                'nav'         => '105.123456',
                'nav_date'    => '2023-10-25',
            ]
        ]);

        $service = new MutualFundNavSyncService($mockProvider);
        $service->sync();

        $this->assertDatabaseHas('mutual_funds', [
            'amfi_code'   => '120503',
            'plan_type'   => 'DIRECT',
            'option_type' => 'GROWTH',
        ]);

        $fund = MutualFund::where('amfi_code', '120503')->first();

        $this->assertDatabaseHas('mutual_fund_navs', [
            'mutual_fund_id' => $fund->id,
            'nav'            => '105.123456',
            'nav_date'       => '2023-10-25',
        ]);
    }

    public function test_it_updates_existing_nav_for_same_date()
    {
        $mockProvider = $this->createMock(MutualFundDataProviderInterface::class);
        $mockProvider->method('fetchLatestNavs')->willReturn([
            [
                'amfi_code'   => '120503',
                'isin'        => 'INF200K01170',
                'amc_name'    => 'SBI Mutual Fund',
                'scheme_name' => 'SBI Flexicap',
                'plan_type'   => 'DIRECT',
                'option_type' => 'GROWTH',
                'category'    => 'Equity',
                'nav'         => '105.123456',
                'nav_date'    => '2023-10-25',
            ]
        ]);

        $service = new MutualFundNavSyncService($mockProvider);
        $service->sync();

        // Change NAV for the same date and sync again
        $mockProvider2 = $this->createMock(MutualFundDataProviderInterface::class);
        $mockProvider2->method('fetchLatestNavs')->willReturn([
            [
                'amfi_code'   => '120503',
                'isin'        => 'INF200K01170',
                'amc_name'    => 'SBI Mutual Fund',
                'scheme_name' => 'SBI Flexicap',
                'plan_type'   => 'DIRECT',
                'option_type' => 'GROWTH',
                'category'    => 'Equity',
                'nav'         => '106.000000', // Changed NAV
                'nav_date'    => '2023-10-25',
            ]
        ]);

        $service2 = new MutualFundNavSyncService($mockProvider2);
        $service2->sync();

        $fund = MutualFund::where('amfi_code', '120503')->first();
        $navs = MutualFundNav::where('mutual_fund_id', $fund->id)->get();

        $this->assertCount(1, $navs);
        $this->assertEquals('106.000000', $navs->first()->nav);
    }
}
