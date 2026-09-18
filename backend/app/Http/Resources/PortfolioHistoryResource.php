<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'valuation_date'   => $this->valuation_date->toDateString(),
            'invested_capital' => number_format((float) $this->invested_capital, 6, '.', ''),
            'market_value'     => number_format((float) $this->market_value, 6, '.', ''),
            'cash_balance'     => number_format((float) $this->cash_balance, 6, '.', ''),
            'total_value'      => number_format((float) $this->total_value, 6, '.', ''),
        ];
    }
}
