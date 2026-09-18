<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioDashboardResource extends JsonResource
{
    public function toArray($request)
    {
        $allocations = $this->rebalancingPlan();

        // Map allocation targets to include only required fields
        $formattedAllocations = array_map(function ($allocation) {
            return [
                'symbol' => $allocation['symbol'],
                'target' => number_format((float) $allocation['target'], 6, '.', ''),
                'current' => number_format((float) $allocation['current'], 6, '.', ''),
                'deviation' => number_format((float) $allocation['deviation'], 6, '.', ''),
            ];
        }, $allocations);

        return [
            'portfolio' => [
                'id' => $this->id,
                'name' => $this->name,
                'base_currency' => $this->base_currency,
            ],
            'summary' => [
                'total_invested_cost' => number_format((float) $this->currentInvestedCost(), 6, '.', ''),
                'current_market_value' => number_format((float) $this->currentMarketValue(), 6, '.', ''),
                'cash_balance' => number_format((float) $this->cashBalance(), 6, '.', ''),
                'total_portfolio_value' => number_format((float) $this->totalPortfolioValue(), 6, '.', ''),
                'unrealized_profit_loss' => number_format((float) $this->unrealizedProfitLoss(), 6, '.', ''),
                'unrealized_profit_loss_percentage' => number_format((float) $this->unrealizedProfitLossPercentage(), 6, '.', ''),
                'realized_profit_loss' => number_format((float) $this->realizedProfitLoss(), 6, '.', ''),
                'realized_profit_loss_percentage' => number_format((float) $this->realizedProfitLossPercentage(), 6, '.', ''),
                'total_profit_loss' => number_format((float) $this->totalProfitLoss(), 6, '.', ''),
                'total_profit_loss_percentage' => number_format((float) $this->totalProfitLossPercentage(), 6, '.', ''),
            ],
            'allocation' => $formattedAllocations,
            'holdings' => PortfolioDashboardHoldingResource::collection($this->holdings),
        ];
    }
}
