<?php

namespace App\Models;

use App\Models\Portfolio;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['portfolio_id', 'symbol', 'target_percentage'])]
class PortfolioAllocation extends Model
{
    protected static function booted(): void
    {
        static::creating(function (PortfolioAllocation $allocation): void {
            if (
                $allocation->target_percentage < 0 ||
                $allocation->target_percentage > 100
            ) {
                throw new \InvalidArgumentException(
                    'Target percentage must be between 0 and 100.'
                );
            }
        });
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function allocationPercentage(): float
    {
        return (float) $this->target_percentage;
    }
}
