<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'portfolio_id',
    'holding_id',
    'type',
    'quantity',
    'price',
    'currency',
    'transaction_date',
])]
#[Hidden([
    'portfolio_id',
    'holding_id',
    'deleted_at',
])]
class Transaction extends Model
{
    use SoftDeletes;

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function holding(): BelongsTo
    {
        return $this->belongsTo(Holding::class);
    }
}