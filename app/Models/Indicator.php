<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indicator extends Model
{
    protected $fillable = ['name', 'short_name', 'category', 'description', 'params', 'outputs'];

    protected $casts = [
        'params'  => 'array',
        'outputs' => 'array',
    ];

    public function conditions(): HasMany
    {
        return $this->hasMany(StrategyCondition::class);
    }
}
