<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'portfolio_id',
    'valuation_date',
    'invested_capital',
    'market_value',
    'cash_balance',
    'total_value',
])]
#[Hidden([
    'portfolio_id',
])]
class PortfolioSnapshot extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $casts = [
        'valuation_date' => 'date',
        'invested_capital' => 'float',
        'market_value' => 'float',
        'cash_balance' => 'float',
        'total_value' => 'float',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
