<?php

namespace App\Services\SipPlanner\Contracts;

use App\Models\Portfolio;

interface SipAllocationStrategy
{
    /**
     * Calculate the SIP allocation plan.
     *
     * @param Portfolio $portfolio
     * @param float $sipAmount
     * @return array Array of suggested allocations: [['symbol' => 'AAPL', 'suggested_amount' => 100.00]]
     */
    public function calculate(Portfolio $portfolio, float $sipAmount): array;
}
