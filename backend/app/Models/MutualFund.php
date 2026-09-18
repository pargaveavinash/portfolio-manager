<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MutualFund extends Model
{
    use HasFactory;

    protected $fillable = [
        'amfi_code',
        'isin',
        'amc_name',
        'scheme_name',
        'plan_type',
        'option_type',
        'category',
        'benchmark_id',
        'sub_category',
        'inception_date',
        'ter',
        'aum',
        'exit_load',
    ];

    protected $casts = [
        'inception_date' => 'date',
        'ter' => 'decimal:4',
        'aum' => 'decimal:2',
    ];

    public function navs(): HasMany
    {
        return $this->hasMany(MutualFundNav::class);
    }

    public function benchmark(): BelongsTo
    {
        return $this->belongsTo(Benchmark::class);
    }
}
