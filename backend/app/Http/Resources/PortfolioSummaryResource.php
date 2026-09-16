<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'portfolio' => [
                'id'   => $this->id,
                'name' => $this->name,
            ],
            'summary' => [
                'total_invested_cost'               => $this->currentInvestedCost(),
                'current_market_value'              => $this->currentMarketValue(),
                'cash_balance'                      => $this->cashBalance(),
                'total_portfolio_value'             => $this->totalPortfolioValue(),
                'unrealized_profit_loss'            => $this->unrealizedProfitLoss(),
                'unrealized_profit_loss_percentage' => $this->unrealizedProfitLossPercentage(),
                'realized_profit_loss'              => $this->realizedProfitLoss(),
                'realized_profit_loss_percentage'   => $this->realizedProfitLossPercentage(),
                'total_profit_loss'                 => $this->totalProfitLoss(),
                'total_profit_loss_percentage'      => $this->totalProfitLossPercentage(),
            ],
        ];
    }
}
