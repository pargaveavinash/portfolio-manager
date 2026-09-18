<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioDashboardHoldingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'name' => $this->name,
            'asset_type' => $this->asset_type,
            'quantity' => number_format((float) $this->currentQuantity(), 6, '.', ''),
            'average_price' => number_format((float) $this->currentAveragePrice(), 6, '.', ''),
            'market_price' => number_format((float) $this->currentMarketPrice(), 6, '.', ''),
            'invested_cost' => number_format((float) $this->currentInvestedCost(), 6, '.', ''),
            'market_value' => number_format((float) $this->currentMarketValue(), 6, '.', ''),
            'unrealized_profit_loss' => number_format((float) $this->unrealizedProfitLoss(), 6, '.', ''),
            'unrealized_profit_loss_percentage' => number_format((float) $this->unrealizedProfitLossPercentage(), 6, '.', ''),
        ];
    }
}
