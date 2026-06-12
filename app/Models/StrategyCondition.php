<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyCondition extends Model
{
    protected $fillable = [
        'strategy_id', 'signal_type', 'indicator_id', 'indicator_output',
        'param_overrides', 'operator', 'value_a', 'value_b', 'next_logic', 'sort_order',
    ];

    protected $casts = [
        'param_overrides' => 'array',
        'sort_order'      => 'integer',
    ];

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(UserStrategy::class, 'strategy_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function humanLabel(): string
    {
        $ind    = $this->indicator->short_name . '(' . $this->indicator_output . ')';
        $opMap  = [
            '>'             => '>',
            '<'             => '<',
            '>='            => '≥',
            '<='            => '≤',
            '='             => '=',
            'between'       => 'между',
            'crosses_above' => 'пересекает вверх',
            'crosses_below' => 'пересекает вниз',
        ];
        $op = $opMap[$this->operator] ?? $this->operator;

        if ($this->operator === 'between') {
            return "{$ind} {$op} {$this->value_a} и {$this->value_b}";
        }

        return "{$ind} {$op} {$this->value_a}";
    }
}
