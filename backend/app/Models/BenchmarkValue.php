<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenchmarkValue extends Model
{
    protected $fillable = [
        'benchmark_id',
        'value',
        'valuation_date',
    ];

    protected $casts = [
        'value' => 'decimal:6',
        'valuation_date' => 'date',
    ];

    public function benchmark(): BelongsTo
    {
        return $this->belongsTo(Benchmark::class);
    }
}
