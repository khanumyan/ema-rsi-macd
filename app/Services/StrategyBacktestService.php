<?php

namespace App\Services;

use App\Models\UserStrategy;
use App\Models\StrategySignal;

class StrategyBacktestService
{
    public function __construct(private StrategyEngineService $engine) {}

    public function run(UserStrategy $strategy, int $periods = 500): array
    {
        $candles = $this->engine->fetchCandles(
            $strategy->symbol,
            $strategy->interval,
            $periods + 100
        );

        if (count($candles) < 60) {
            return ['error' => 'Not enough historical candles'];
        }

        $strategy->load(['buyConditions.indicator', 'sellConditions.indicator']);

        $signals  = [];
        $window   = max(50, $strategy->candles_limit);

        for ($i = $window; $i < count($candles) - 1; $i++) {
            $slice   = array_slice($candles, 0, $i + 1);
            $indicators = $this->engine->calcAllIndicators($slice, $strategy);

            foreach (['BUY', 'SELL'] as $type) {
                $conditions = $type === 'BUY'
                    ? $strategy->buyConditions
                    : $strategy->sellConditions;

                if ($conditions->isEmpty()) continue;
                if (!$this->checkConditions($conditions, $indicators)) continue;

                $close = $indicators['price.close'];
                $atr   = $indicators['atr.atr'] ?? ($close * 0.02);

                [$tp, $sl] = $strategy->tp_sl_mode === 'atr'
                    ? ($type === 'BUY'
                        ? [$close + $atr * $strategy->tp_multiplier, $close - $atr * $strategy->sl_multiplier]
                        : [$close - $atr * $strategy->tp_multiplier, $close + $atr * $strategy->sl_multiplier])
                    : ($type === 'BUY'
                        ? [$close * (1 + $strategy->tp_multiplier / 100), $close * (1 - $strategy->sl_multiplier / 100)]
                        : [$close * (1 - $strategy->tp_multiplier / 100), $close * (1 + $strategy->sl_multiplier / 100)]);

                $status = $this->resolveStatus($candles, $i + 1, $type, $tp, $sl);

                $signals[] = [
                    'type'         => $type,
                    'price'        => $close,
                    'take_profit'  => $tp,
                    'stop_loss'    => $sl,
                    'atr'          => $atr,
                    'status'       => $status,
                    'triggered_at' => date('Y-m-d H:i:s', (int)($candles[$i]['open_time'] / 1000)),
                    'profit_pct'   => $status === 'DONE'
                        ? ($type === 'BUY' ? ($tp - $close) / $close * 100 : ($close - $tp) / $close * 100)
                        : ($type === 'BUY' ? ($sl - $close) / $close * 100 : ($close - $sl) / $close * 100),
                ];
            }
        }

        return [
            'signals'  => $signals,
            'monthly'  => $this->buildMonthlyStats($signals),
            'summary'  => $this->buildSummary($signals),
        ];
    }

    private function resolveStatus(array $candles, int $fromIdx, string $type, float $tp, float $sl): string
    {
        for ($i = $fromIdx; $i < count($candles); $i++) {
            $high = $candles[$i]['high'];
            $low  = $candles[$i]['low'];

            if ($type === 'BUY') {
                if ($high >= $tp) return 'DONE';
                if ($low  <= $sl) return 'MISSED';
            } else {
                if ($low  <= $tp) return 'DONE';
                if ($high >= $sl) return 'MISSED';
            }
        }
        return 'PROCESSING';
    }

    private function checkConditions(\Illuminate\Support\Collection $conditions, array $indicators): bool
    {
        $result       = null;
        $pendingLogic = 'AND';

        foreach ($conditions as $cond) {
            $key = strtolower($cond->indicator->short_name) . '.' . $cond->indicator_output;
            $val = $indicators[$key] ?? null;
            if ($val === null) continue;

            $pass = $this->evalCondition($val, $cond->operator, $cond->value_a, $cond->value_b);

            if ($result === null) {
                $result = $pass;
            } elseif ($pendingLogic === 'AND') {
                $result = $result && $pass;
            } else {
                $result = $result || $pass;
            }

            $pendingLogic = $cond->next_logic ?? 'AND';
        }

        return (bool)$result;
    }

    private function evalCondition(float $val, string $op, ?float $a, ?float $b): bool
    {
        return match($op) {
            '>'       => $val > $a,
            '>='      => $val >= $a,
            '<'       => $val < $a,
            '<='      => $val <= $a,
            '='       => abs($val - $a) < 0.000001,
            'between' => $a !== null && $b !== null && $val >= $a && $val <= $b,
            default   => false,
        };
    }

    private function buildMonthlyStats(array $signals): array
    {
        $monthly = [];
        foreach ($signals as $s) {
            $month = substr($s['triggered_at'], 0, 7);
            if (!isset($monthly[$month])) {
                $monthly[$month] = ['total' => 0, 'done' => 0, 'missed' => 0, 'profit_pct' => 0.0];
            }
            $monthly[$month]['total']++;
            if ($s['status'] === 'DONE')   $monthly[$month]['done']++;
            if ($s['status'] === 'MISSED') $monthly[$month]['missed']++;
            $monthly[$month]['profit_pct'] += $s['profit_pct'];
        }
        ksort($monthly);
        return $monthly;
    }

    private function buildSummary(array $signals): array
    {
        $total  = count($signals);
        $done   = count(array_filter($signals, fn($s) => $s['status'] === 'DONE'));
        $missed = count(array_filter($signals, fn($s) => $s['status'] === 'MISSED'));
        $profit = array_sum(array_column($signals, 'profit_pct'));

        return [
            'total'      => $total,
            'done'       => $done,
            'missed'     => $missed,
            'win_rate'   => $total > 0 ? round($done / $total * 100, 1) : 0,
            'total_pct'  => round($profit, 2),
            'avg_pct'    => $total > 0 ? round($profit / $total, 2) : 0,
        ];
    }
}
