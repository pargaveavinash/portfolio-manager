<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'portfolio_id',
    'type',
    'amount',
    'currency',
    'transaction_date',
    'notes',
])]
#[Hidden([
    'portfolio_id',
    'deleted_at',
])]
class CashTransaction extends Model
{
    use SoftDeletes;

    protected $casts = [
        'amount' => 'float',
        'transaction_date' => 'datetime',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}