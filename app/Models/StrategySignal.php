<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategySignal extends Model
{
    protected $fillable = [
        'strategy_id', 'user_id', 'symbol', 'interval', 'type',
        'price', 'take_profit', 'stop_loss', 'atr',
        'indicator_values', 'status', 'is_backtest',
        'triggered_at', 'resolved_at',
    ];

    protected $casts = [
        'indicator_values' => 'array',
        'is_backtest'      => 'boolean',
        'triggered_at'     => 'datetime',
        'resolved_at'      => 'datetime',
        'price'            => 'float',
        'take_profit'      => 'float',
        'stop_loss'        => 'float',
        'atr'              => 'float',
    ];

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(UserStrategy::class, 'strategy_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profitPct(): float
    {
        if (!$this->take_profit || !$this->price) return 0;
        return $this->type === 'BUY'
            ? ($this->take_profit - $this->price) / $this->price * 100
            : ($this->price - $this->take_profit) / $this->price * 100;
    }

    public function lossPct(): float
    {
        if (!$this->stop_loss || !$this->price) return 0;
        return $this->type === 'BUY'
            ? ($this->price - $this->stop_loss) / $this->price * 100
            : ($this->stop_loss - $this->price) / $this->price * 100;
    }
}
