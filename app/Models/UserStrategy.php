<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserStrategy extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'symbol', 'interval', 'candles_limit',
        'tp_sl_mode', 'tp_multiplier', 'sl_multiplier',
        'mode', 'profile_id', 'telegram_chat_id', 'is_active',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'tp_multiplier'  => 'float',
        'sl_multiplier'  => 'float',
        'candles_limit'  => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(StrategyCondition::class, 'strategy_id')->orderBy('signal_type')->orderBy('sort_order');
    }

    public function buyConditions(): HasMany
    {
        return $this->hasMany(StrategyCondition::class, 'strategy_id')
            ->where('signal_type', 'BUY')
            ->orderBy('sort_order');
    }

    public function sellConditions(): HasMany
    {
        return $this->hasMany(StrategyCondition::class, 'strategy_id')
            ->where('signal_type', 'SELL')
            ->orderBy('sort_order');
    }

    public function signals(): HasMany
    {
        return $this->hasMany(StrategySignal::class, 'strategy_id');
    }
}
