<?php

namespace Tests\Unit\Services\MarketData;

use App\Models\MutualFund;
use App\Models\MutualFundNav;
use App\Services\MarketData\MarketDataValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketDataValuationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_retrieves_latest_applicable_nav_for_current_valuation()
    {
        $fund = MutualFund::create([
            'amfi_code'   => '120503',
            'amc_name'    => 'SBI',
            'scheme_name' => 'SBI Flexicap',
            'plan_type'   => 'DIRECT',
            'option_type' => 'GROWTH',
        ]);

        MutualFundNav::create([
            'mutual_fund_id' => $fund->id,
            'nav'            => '100.000000',
            'nav_date'       => '2023-10-24',
        ]);

        MutualFundNav::create([
            'mutual_fund_id' => $fund->id,
            'nav'            => '105.000000',
            'nav_date'       => '2023-10-25',
        ]);

        $service = new MarketDataValuationService();
        $nav = $service->getApplicableNav('120503', 'MUTUAL_FUND');

        $this->assertEquals('105.000000', $nav);
    }

    public function test_it_retrieves_historical_nav_using_nav_date_less_than_or_equal_to_requested_date()
    {
        $fund = MutualFund::create([
            'amfi_code'   => '120503',
            'amc_name'    => 'SBI',
            'scheme_name' => 'SBI Flexicap',
            'plan_type'   => 'DIRECT',
            'option_type' => 'GROWTH',
        ]);

        MutualFundNav::create([
            'mutual_fund_id' => $fund->id,
            'nav'            => '100.000000',
            'nav_date'       => '2023-10-20', // Friday
        ]);

        MutualFundNav::create([
            'mutual_fund_id' => $fund->id,
            'nav'            => '105.000000',
            'nav_date'       => '2023-10-23', // Monday
        ]);

        $service = new MarketDataValuationService();

        // Requesting for Sunday (2023-10-22) should return Friday's NAV
        $nav = $service->getApplicableNav('120503', 'MUTUAL_FUND', '2023-10-22');

        $this->assertEquals('100.000000', $nav);
    }

    public function test_it_returns_null_for_missing_nav()
    {
        $service = new MarketDataValuationService();
        $nav = $service->getApplicableNav('999999', 'MUTUAL_FUND');

        $this->assertNull($nav);
    }
}
