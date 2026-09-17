<?php

namespace Tests\Unit\Services\MarketData;

use App\Services\MarketData\Providers\AmfiMutualFundProvider;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AmfiMutualFundProviderTest extends TestCase
{
    public function test_it_parses_valid_amfi_payload()
    {
        Http::fake([
            '*' => Http::response(
                "Scheme Code;ISIN Div Payout/ ISIN Growth;ISIN Div Reinvestment;Scheme Name;Net Asset Value;Date\n" .
                "120503;INF200K01170;INF200K01188;SBI Flexicap Fund - Direct Plan - Growth;105.1234;25-Oct-2023\n"
            ),
        ]);

        $provider = new AmfiMutualFundProvider();
        $navs = $provider->fetchLatestNavs();

        $this->assertCount(1, $navs);
        $this->assertEquals('120503', $navs[0]['amfi_code']);
        $this->assertEquals('INF200K01170', $navs[0]['isin']);
        $this->assertStringContainsString('SBI Flexicap Fund', $navs[0]['scheme_name']);
        $this->assertEquals('105.1234', $navs[0]['nav']);
        $this->assertEquals('2023-10-25', $navs[0]['nav_date']);
        $this->assertEquals('DIRECT', $navs[0]['plan_type']);
        $this->assertEquals('GROWTH', $navs[0]['option_type']);
    }

    public function test_it_skips_malformed_rows_and_processes_partial_valid_payload()
    {
        Http::fake([
            '*' => Http::response(
                "Scheme Code;ISIN Div Payout/ ISIN Growth;ISIN Div Reinvestment;Scheme Name;Net Asset Value;Date\n" .
                "120503;INF200K01170;INF200K01188;SBI Flexicap Fund - Direct Plan - Growth;105.1234;25-Oct-2023\n" .
                "MALFORMED_LINE_WITHOUT_SEMICOLONS\n" .
                "120504;INF200K01196;INF200K01204;SBI Flexicap Fund - Regular Plan - Growth;N.A.;25-Oct-2023\n" . // Missing NAV
                "120505;INF200K01171;INF200K01189;HDFC Flexicap Fund - Direct Plan - Growth;90.5555;25-Oct-2023\n"
            ),
        ]);

        $provider = new AmfiMutualFundProvider();
        $navs = $provider->fetchLatestNavs();

        $this->assertCount(2, $navs);
        $this->assertEquals('120503', $navs[0]['amfi_code']);
        $this->assertEquals('120505', $navs[1]['amfi_code']);
    }

    public function test_it_handles_empty_payload()
    {
        Http::fake([
            '*' => Http::response(""),
        ]);

        $provider = new AmfiMutualFundProvider();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Empty payload from AMFI');

        $provider->fetchLatestNavs();
    }

    public function test_it_handles_provider_network_failure()
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $provider = new AmfiMutualFundProvider();

        $this->expectException(RequestException::class);
        $provider->fetchLatestNavs();
    }
}
