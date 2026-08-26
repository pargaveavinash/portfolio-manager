<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'symbol',
    'name',
    'asset_type',
    'quantity',
    'average_price',
    'currency',
])]
#[Hidden([
    'portfolio_id',
    'deleted_at',
])]
class Holding extends Model
{
    use HasFactory, SoftDeletes;

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
