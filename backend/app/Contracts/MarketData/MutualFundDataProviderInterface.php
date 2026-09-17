<?php

namespace App\Contracts\MarketData;

interface MutualFundDataProviderInterface
{
    /**
     * Fetch the latest NAVs for mutual funds.
     *
     * @return array
     * @throws \Exception
     */
    public function fetchLatestNavs(): array;
}
