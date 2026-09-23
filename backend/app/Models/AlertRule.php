<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'reference_type',
        'reference_id',
        'threshold_value',
        'is_active',
        'last_evaluated_state',
    ];

    protected $casts = [
        'threshold_value' => 'decimal:6',
        'is_active' => 'boolean',
        'last_evaluated_state' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
