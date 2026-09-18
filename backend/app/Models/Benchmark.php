<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Benchmark extends Model
{
    protected $fillable = [
        'name',
        'code',
        'variant',
    ];

    public function mutualFunds(): HasMany
    {
        return $this->hasMany(MutualFund::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(BenchmarkValue::class);
    }
}
