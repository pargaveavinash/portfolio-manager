<?php

namespace App\Services\SipPlanner;

use App\Models\Portfolio;
use App\Services\SipPlanner\Contracts\SipAllocationStrategy;
use App\Services\SipPlanner\Strategies\OptimizationStrategy;

class SipPlannerService
{
    /**
     * Generate a SIP plan using the specified strategy.
     *
     * @param Portfolio $portfolio
     * @param float $amount
     * @param string $strategyName
     * @return array
     */
    public function generatePlan(Portfolio $portfolio, float $amount, string $strategyName = 'optimization'): array
    {
        $strategy = $this->resolveStrategy($strategyName);
        return $strategy->calculate($portfolio, $amount);
    }

    /**
     * Resolve the strategy name to a concrete implementation.
     *
     * @param string $strategyName
     * @return SipAllocationStrategy
     * @throws \InvalidArgumentException
     */
    protected function resolveStrategy(string $strategyName): SipAllocationStrategy
    {
        return match ($strategyName) {
            'optimization' => new OptimizationStrategy(),
            default => throw new \InvalidArgumentException("Strategy '{$strategyName}' is not supported."),
        };
    }
}
