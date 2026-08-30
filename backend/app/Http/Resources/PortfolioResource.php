<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                                => $this->id,
            'name'                              => $this->name,
            'description'                       => $this->description,
            'base_currency'                     => $this->base_currency,
            'created_at'                        => $this->created_at,
            'updated_at'                        => $this->updated_at,

            'current_invested_cost'             => $this->currentInvestedCost(),
            'current_market_value'              => $this->currentMarketValue(),
            'unrealized_profit_loss'            => $this->unrealizedProfitLoss(),
            'unrealized_profit_loss_percentage' => $this->unrealizedProfitLossPercentage(),
            'realized_profit_loss'              => $this->realizedProfitLoss(),
            'realized_profit_loss_percentage'   => $this->realizedProfitLossPercentage(),
            'total_profit_loss'                 => $this->totalProfitLoss(),
            'total_profit_loss_percentage'      => $this->totalProfitLossPercentage(),
        ];
    }
}
