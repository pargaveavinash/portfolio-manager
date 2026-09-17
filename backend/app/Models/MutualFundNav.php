<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutualFundNav extends Model
{
    use HasFactory;

    protected $fillable = [
        'mutual_fund_id',
        'nav',
        'nav_date',
    ];

    public function mutualFund(): BelongsTo
    {
        return $this->belongsTo(MutualFund::class);
    }
}
