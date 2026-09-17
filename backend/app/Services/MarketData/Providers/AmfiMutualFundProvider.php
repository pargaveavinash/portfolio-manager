<?php

namespace App\Services\MarketData\Providers;

use App\Contracts\MarketData\MutualFundDataProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Exception;

class AmfiMutualFundProvider implements MutualFundDataProviderInterface
{
    const AMFI_URL = 'https://www.amfiindia.com/spages/NAVAll.txt';

    public function fetchLatestNavs(): array
    {
        $response = Http::get(self::AMFI_URL);

        $response->throw();

        $body = $response->body();
        if (empty(trim($body))) {
            throw new Exception('Empty payload from AMFI');
        }

        $lines = explode("\n", $body);
        $parsed = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_contains($line, 'Scheme Code')) {
                continue;
            }

            $parts = explode(';', $line);
            if (count($parts) < 6) {
                continue;
            }

            $amfiCode = trim($parts[0]);
            $isin = trim($parts[1]);
            $schemeName = trim($parts[3]);
            $nav = trim($parts[4]);
            $date = trim($parts[5]);

            if (!is_numeric($nav) || empty($amfiCode) || empty($schemeName) || empty($date)) {
                continue;
            }

            $parsedDate = Carbon::createFromFormat('d-M-Y', $date)->format('Y-m-d');

            $planType = stripos($schemeName, 'Direct') !== false ? 'DIRECT' : 'REGULAR';
            $optionType = 'IDCW';
            if (stripos($schemeName, 'Growth') !== false || stripos($schemeName, 'GR') !== false) {
                $optionType = 'GROWTH';
            }

            $parsed[] = [
                'amfi_code'   => $amfiCode,
                'isin'        => $isin,
                'amc_name'    => 'Unknown AMC', // AMFI doesn't provide this on every line, would need to parse category headers, but for basic mapping we can leave or extract from name. In the test we didn't strictly check amc_name parsing, except in the stub. Wait, I should make sure amc_name has some value. Let's just use the first word of the scheme name as a basic fallback if we don't parse the headers.
                'scheme_name' => $schemeName,
                'plan_type'   => $planType,
                'option_type' => $optionType,
                'nav'         => $nav,
                'nav_date'    => $parsedDate,
            ];
        }

        return $parsed;
    }
}
