<?php

namespace App\Services\MarketData;

use App\Models\MutualFund;
use App\Models\MutualFundNav;
use Illuminate\Support\Carbon;

class MarketDataValuationService
{
    /**
     * Get the applicable NAV for a given symbol (AMFI code) and date.
     *
     * @param string $symbol
     * @param string $assetType
     * @param string|null $date
     * @return string|null
     */
    public function getApplicableNav(string $symbol, string $assetType, ?string $date = null): ?string
    {
        if ($assetType !== 'MUTUAL_FUND') {
            return null; // For Phase 11A, we only handle mutual funds
        }

        $fund = MutualFund::where('amfi_code', $symbol)->first();
        if (!$fund) {
            return null;
        }

        $query = MutualFundNav::where('mutual_fund_id', $fund->id);

        if ($date) {
            // Apply historical rule: nav_date <= requested date
            $query->where('nav_date', '<=', $date);
        }

        // Order by nav_date descending to get the most recent applicable NAV
        $navRecord = $query->orderBy('nav_date', 'desc')->first();

        return $navRecord ? bcadd((string) $navRecord->nav, '0', 6) : null;
    }
}
