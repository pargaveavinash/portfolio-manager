<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskFreeRateValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'risk_free_rate_id',
        'valuation_date',
        'rate',
    ];

    protected $casts = [
        'valuation_date' => 'date',
        'rate' => 'float',
    ];

    public function riskFreeRate(): BelongsTo
    {
        return $this->belongsTo(RiskFreeRate::class);
    }

    protected static function booted()
    {
        static::saving(function ($value) {
            if ($value->rate < 0) {
                throw new \InvalidArgumentException('Rate cannot be negative.');
            }
        });
    }
}
