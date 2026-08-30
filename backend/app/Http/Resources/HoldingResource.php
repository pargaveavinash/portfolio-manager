<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HoldingResource extends JsonResource
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
            'symbol'                            => $this->symbol,
            'name'                              => $this->name,
            'asset_type'                        => $this->asset_type,
            'quantity'                          => $this->currentQuantity(),
            'average_price'                     => $this->currentAveragePrice(),
            'currency'                          => $this->currency,
            'market_price'                      => $this->currentMarketPrice(),
            'invested_cost'                     => $this->currentInvestedCost(),
            'market_value'                      => $this->currentMarketValue(),
            'unrealized_profit_loss'            => $this->unrealizedProfitLoss(),
            'unrealized_profit_loss_percentage' => $this->unrealizedProfitLossPercentage(),
            'realized_profit_loss'              => $this->realizedProfitLoss(),
            'realized_profit_loss_percentage'   => $this->realizedProfitLossPercentage(),
        ];
    }
}