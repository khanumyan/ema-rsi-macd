<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Сигналы из таблицы crypto_sygnals_new (структура как у crypto_signals).
 */
class CryptoSignalNew extends Model
{
    protected $table = 'crypto_sygnals_new';

    protected $fillable = [
        'flow_id',
        'symbol',
        'strategy',
        'type',
        'strength',
        'price',
        'rsi',
        'ema',
        'ema_slow',
        'macd',
        'macd_signal',
        'macd_histogram',
        'atr',
        'stop_loss',
        'take_profit',
        'volume_ratio',
        'htf_trend',
        'htf_rsi',
        'ltf_rsi',
        'long_score',
        'short_score',
        'long_probability',
        'short_probability',
        'interval',
        'limit',
        'reason',
        'sent_to_telegram',
        'signal_time',
        'status',
        'updated_at',
    ];

    protected $casts = [
        'flow_id' => 'string',
        'price' => 'decimal:8',
        'rsi' => 'decimal:4',
        'ema' => 'decimal:8',
        'ema_slow' => 'decimal:8',
        'macd' => 'decimal:8',
        'macd_signal' => 'decimal:8',
        'macd_histogram' => 'decimal:8',
        'atr' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'take_profit' => 'decimal:8',
        'volume_ratio' => 'decimal:4',
        'htf_rsi' => 'decimal:4',
        'ltf_rsi' => 'decimal:4',
        'long_score' => 'integer',
        'short_score' => 'integer',
        'long_probability' => 'integer',
        'short_probability' => 'integer',
        'limit' => 'integer',
        'sent_to_telegram' => 'boolean',
        'signal_time' => 'datetime',
    ];
}
