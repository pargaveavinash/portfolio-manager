<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    public function navs(): HasMany
    {
        return $this->hasMany(MutualFundNav::class);
    }
}
