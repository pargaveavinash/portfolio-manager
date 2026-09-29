<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SipPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'portfolio_id' => $this->portfolio_id,
            'amount' => (float) $this->amount,
            'strategy' => $this->strategy,
            'frequency' => $this->frequency,
            'status' => $this->status,
            'start_date' => $this->start_date ? $this->start_date->toDateString() : null,
            'next_scheduled_date' => $this->next_scheduled_date ? $this->next_scheduled_date->toDateString() : null,
            'end_date' => $this->end_date ? $this->end_date->toDateString() : null,
            'timezone' => $this->timezone,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
