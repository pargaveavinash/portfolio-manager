<?php

namespace Tests\Unit\Models;

use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use App\Models\MutualFund;
use App\Models\MutualFundNav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HoldingValuationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mutual_fund_holding_uses_market_data_service_for_current_valuation()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $fund = MutualFund::create([
            'amfi_code'   => '120503',
            'amc_name'    => 'SBI',
            'scheme_name' => 'SBI Flexicap',
            'plan_type'   => 'DIRECT',
            'option_type' => 'GROWTH',
        ]);

        MutualFundNav::create([
            'mutual_fund_id' => $fund->id,
            'nav'            => '105.000000',
            'nav_date'       => '2023-10-25',
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => '120503',
            'name'          => 'SBI Flexicap',
            'asset_type'    => 'MUTUAL_FUND',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 100,
            'currency'         => 'INR',
            'transaction_date' => '2023-10-20 10:00:00',
        ]);

        // Current Quantity = 10
        // Latest NAV = 105.00
        // Market Value = 1050.00

        $this->assertEquals(1050.00, $holding->currentMarketValue());
    }

    public function test_mutual_fund_holding_throws_exception_when_nav_is_missing()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => '999999',
            'name'          => 'Unknown Fund',
            'asset_type'    => 'MUTUAL_FUND',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 100,
            'currency'         => 'INR',
            'transaction_date' => '2023-10-20 10:00:00',
        ]);

        $this->expectException(\App\Exceptions\MissingMarketDataException::class);
        $holding->currentMarketValue();
    }

    public function test_mutual_fund_holding_preserves_precision_without_float_loss_for_large_values()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $fund = MutualFund::create([
            'amfi_code'   => '120504',
            'amc_name'    => 'SBI',
            'scheme_name' => 'SBI Giant',
            'plan_type'   => 'DIRECT',
            'option_type' => 'GROWTH',
        ]);

        MutualFundNav::create([
            'mutual_fund_id' => $fund->id,
            'nav'            => '15.123456',
            'nav_date'       => '2023-10-25',
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => '120504',
            'name'          => 'SBI Giant',
            'asset_type'    => 'MUTUAL_FUND',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        // Very large quantity: 9,999,999.123
        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 9999999.123,
            'price'            => 100,
            'currency'         => 'INR',
            'transaction_date' => '2023-10-20 10:00:00',
        ]);

        // Market Value = 9999999.123 * 15.123456 = 151234546.736729088
        $this->assertEquals(151234546.736729088, $holding->currentMarketValue());
    }

    public function test_mutual_fund_holding_historical_valuation_uses_friday_nav_for_sunday()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $fund = MutualFund::create([
            'amfi_code'   => '120505',
            'amc_name'    => 'SBI',
            'scheme_name' => 'SBI Date Test',
            'plan_type'   => 'DIRECT',
            'option_type' => 'GROWTH',
        ]);

        // Friday
        MutualFundNav::create([
            'mutual_fund_id' => $fund->id,
            'nav'            => '100.000000',
            'nav_date'       => '2023-10-20', // Friday
        ]);

        // Monday (Future)
        MutualFundNav::create([
            'mutual_fund_id' => $fund->id,
            'nav'            => '110.000000',
            'nav_date'       => '2023-10-23', // Monday
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => '120505',
            'name'          => 'SBI Date Test',
            'asset_type'    => 'MUTUAL_FUND',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 90,
            'currency'         => 'INR',
            'transaction_date' => '2023-10-15 10:00:00',
        ]);

        $sunday = \Illuminate\Support\Carbon::parse('2023-10-22');

        // Should pick Friday's NAV (100) instead of Monday's (110)
        $this->assertEquals(1000.0, $holding->historicalMarketValue($sunday));
    }
}
