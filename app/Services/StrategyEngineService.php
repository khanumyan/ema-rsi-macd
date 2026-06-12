<?php

namespace App\Services;

use App\Models\UserStrategy;
use App\Models\StrategySignal;
use App\Models\StrategyCondition;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StrategyEngineService
{
    public function runStrategy(UserStrategy $strategy): array
    {
        if ($strategy->symbol === 'ALL') {
            return $this->runForAllSymbols($strategy);
        }

        return $this->runForSymbol($strategy, $strategy->symbol);
    }

    private function runForAllSymbols(UserStrategy $strategy): array
    {
        $symbols = $this->fetchAllSymbols();
        $all = [];
        foreach ($symbols as $symbol) {
            $signals = $this->runForSymbol($strategy, $symbol);
            $all = array_merge($all, $signals);
        }
        return $all;
    }

    public function fetchAllSymbols(): array
    {
        $response = Http::timeout(10)->get('https://fapi.binance.com/fapi/v1/exchangeInfo');
        if (!$response->ok()) return ['BTCUSDT', 'ETHUSDT'];

        return collect($response->json('symbols', []))
            ->where('status', 'TRADING')
            ->where('quoteAsset', 'USDT')
            ->pluck('symbol')
            ->values()
            ->all();
    }

    private function runForSymbol(UserStrategy $strategy, string $symbol): array
    {
        $candles = $this->fetchCandles($symbol, $strategy->interval, $strategy->candles_limit + 50);
        if (count($candles) < 30) {
            return [];
        }

        $indicators = $this->calcAllIndicators($candles, $strategy);

        $signals = [];
        foreach (['BUY', 'SELL'] as $type) {
            $conditions = $type === 'BUY'
                ? $strategy->buyConditions()->with('indicator')->get()
                : $strategy->sellConditions()->with('indicator')->get();

            if ($conditions->isEmpty()) continue;

            if ($this->checkConditions($conditions, $indicators)) {
                $signal = $this->createSignalForSymbol($strategy, $type, $symbol, $candles, $indicators);
                $signals[] = $signal;

                if ($strategy->mode === 'telegram' && $strategy->telegram_chat_id) {
                    $this->sendTelegram($strategy, $signal);
                } elseif ($strategy->mode === 'autotrading' && $strategy->profile) {
                    $this->executeTrade($strategy, $signal);
                }
            }
        }

        return $signals;
    }

    public function fetchCandles(string $symbol, string $interval, int $limit = 200): array
    {
        $url = 'https://fapi.binance.com/fapi/v1/klines';
        $response = Http::timeout(15)->get($url, [
            'symbol'   => strtoupper($symbol),
            'interval' => $interval,
            'limit'    => min($limit, 1500),
        ]);

        if (!$response->ok()) return [];

        return array_map(fn($k) => [
            'open_time' => $k[0],
            'open'      => (float)$k[1],
            'high'      => (float)$k[2],
            'low'       => (float)$k[3],
            'close'     => (float)$k[4],
            'volume'    => (float)$k[5],
        ], $response->json());
    }

    public function calcAllIndicators(array $candles, UserStrategy $strategy): array
    {
        $closes  = array_column($candles, 'close');
        $highs   = array_column($candles, 'high');
        $lows    = array_column($candles, 'low');
        $volumes = array_column($candles, 'volume');
        $n       = count($closes);
        $last    = $n - 1;

        $values = [];
        $values['price.close']  = $closes[$last];
        $values['price.open']   = $candles[$last]['open'];
        $values['price.high']   = $highs[$last];
        $values['price.low']    = $lows[$last];
        $values['price.volume'] = $volumes[$last];

        // RSI
        $values['rsi.rsi'] = $this->calcRSI($closes, 14);

        // EMA
        foreach ([9, 20, 50, 100, 200] as $p) {
            $values["ema_{$p}.ema"] = $this->calcEMA($closes, $p);
        }

        // SMA
        foreach ([20, 50, 200] as $p) {
            $values["sma_{$p}.sma"] = $this->calcSMA($closes, $p);
        }

        // MACD
        [$macd, $signal, $hist] = $this->calcMACD($closes, 12, 26, 9);
        $values['macd.macd']      = $macd;
        $values['macd.signal']    = $signal;
        $values['macd.histogram'] = $hist;

        // ATR
        $values['atr.atr'] = $this->calcATR($highs, $lows, $closes, 14);

        // Bollinger Bands
        [$upper, $middle, $lower] = $this->calcBB($closes, 20, 2.0);
        $values['bb.upper']  = $upper;
        $values['bb.middle'] = $middle;
        $values['bb.lower']  = $lower;

        // Stochastic
        [$k, $d] = $this->calcStoch($highs, $lows, $closes, 14, 3, 3);
        $values['stoch.k'] = $k;
        $values['stoch.d'] = $d;

        // CCI
        $values['cci.cci'] = $this->calcCCI($highs, $lows, $closes, 20);

        // Williams %R
        $values['willr.willr'] = $this->calcWilliamsR($highs, $lows, $closes, 14);

        // ADX
        [$adx, $diPlus, $diMinus] = $this->calcADX($highs, $lows, $closes, 14);
        $values['adx.adx']      = $adx;
        $values['adx.di_plus']  = $diPlus;
        $values['adx.di_minus'] = $diMinus;

        // OBV
        $values['obv.obv'] = $this->calcOBV($closes, $volumes);

        // Supertrend
        [$st, $dir] = $this->calcSupertrend($highs, $lows, $closes, 10, 3.0);
        $values['supertrend.supertrend'] = $st;
        $values['supertrend.direction']  = $dir;

        // Parabolic SAR
        $values['sar.sar'] = $this->calcParabolicSAR($highs, $lows, 0.02, 0.2);

        // CMF
        $values['cmf.cmf'] = $this->calcCMF($highs, $lows, $closes, $volumes, 20);

        // MFI
        $values['mfi.mfi'] = $this->calcMFI($highs, $lows, $closes, $volumes, 14);

        // ROC
        $values['roc.roc'] = $this->calcROC($closes, 12);

        // VWAP (session approximation using available candles)
        $values['vwap.vwap'] = $this->calcVWAP($candles);

        return $values;
    }

    private function checkConditions(\Illuminate\Support\Collection $conditions, array $indicators): bool
    {
        $result = null;
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
            '>'             => $val > $a,
            '>='            => $val >= $a,
            '<'             => $val < $a,
            '<='            => $val <= $a,
            '='             => abs($val - $a) < 0.000001,
            'between'       => $a !== null && $b !== null && $val >= $a && $val <= $b,
            'crosses_above' => $val > $a,
            'crosses_below' => $val < $a,
            default         => false,
        };
    }

    private function createSignal(UserStrategy $strategy, string $type, array $candles, array $indicators): StrategySignal
    {
        return $this->createSignalForSymbol($strategy, $type, $strategy->symbol, $candles, $indicators);
    }

    private function createSignalForSymbol(UserStrategy $strategy, string $type, string $symbol, array $candles, array $indicators): StrategySignal
    {
        $close = $indicators['price.close'];
        $atr   = $indicators['atr.atr'] ?? ($close * 0.02);

        [$tp, $sl] = $strategy->tp_sl_mode === 'atr'
            ? ($type === 'BUY'
                ? [$close + $atr * $strategy->tp_multiplier, $close - $atr * $strategy->sl_multiplier]
                : [$close - $atr * $strategy->tp_multiplier, $close + $atr * $strategy->sl_multiplier])
            : ($type === 'BUY'
                ? [$close * (1 + $strategy->tp_multiplier / 100), $close * (1 - $strategy->sl_multiplier / 100)]
                : [$close * (1 - $strategy->tp_multiplier / 100), $close * (1 + $strategy->sl_multiplier / 100)]);

        return StrategySignal::create([
            'strategy_id'      => $strategy->id,
            'user_id'          => $strategy->user_id,
            'symbol'           => $symbol,
            'interval'         => $strategy->interval,
            'type'             => $type,
            'price'            => $close,
            'take_profit'      => round($tp, 8),
            'stop_loss'        => round($sl, 8),
            'atr'              => $atr,
            'indicator_values' => $indicators,
            'status'           => 'PROCESSING',
            'is_backtest'      => false,
            'triggered_at'     => now(),
        ]);
    }

    private function sendTelegram(UserStrategy $strategy, StrategySignal $signal): void
    {
        $chatId = $strategy->telegram_chat_id;
        if (!$chatId) return;

        $emoji = $signal->type === 'BUY' ? '🟢' : '🔴';
        $text  = "{$emoji} *{$signal->type}* `{$signal->symbol}` — стратегия: *{$strategy->name}*\n"
               . "📍 Вход: `{$signal->price}`\n"
               . "🎯 TP: `{$signal->take_profit}`\n"
               . "🛡 SL: `{$signal->stop_loss}`\n"
               . "⏱ {$strategy->interval} | " . now()->format('H:i d.m.Y');

        $botToken = config('services.telegram.bot_token');
        if (!$botToken) return;

        Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    private function executeTrade(UserStrategy $strategy, StrategySignal $signal): void
    {
        if (!$strategy->profile) return;

        try {
            $futures = app(BinanceFuturesService::class, ['profile' => $strategy->profile]);
            $side    = $signal->type === 'BUY' ? 'BUY' : 'SELL';
            $futures->placeMarketOrder($signal->symbol, $side, 10);
        } catch (\Exception $e) {
            Log::error("AutoTrade failed: {$e->getMessage()}");
        }
    }

    // ─── Indicator calculations ──────────────────────────────────────────────

    public function calcRSI(array $closes, int $period = 14): float
    {
        $n = count($closes);
        if ($n < $period + 1) return 50.0;

        $gains = $losses = 0.0;
        for ($i = $n - $period; $i < $n; $i++) {
            $diff = $closes[$i] - $closes[$i - 1];
            if ($diff > 0) $gains += $diff;
            else $losses += abs($diff);
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;
        if ($avgLoss == 0) return 100.0;

        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }

    public function calcEMA(array $closes, int $period): float
    {
        $n = count($closes);
        if ($n < $period) return end($closes);

        $k   = 2 / ($period + 1);
        $ema = array_sum(array_slice($closes, 0, $period)) / $period;
        for ($i = $period; $i < $n; $i++) {
            $ema = $closes[$i] * $k + $ema * (1 - $k);
        }
        return $ema;
    }

    public function calcSMA(array $closes, int $period): float
    {
        $n = count($closes);
        if ($n < $period) return end($closes);
        return array_sum(array_slice($closes, -$period)) / $period;
    }

    public function calcMACD(array $closes, int $fast = 12, int $slow = 26, int $signal = 9): array
    {
        $emaFast = $this->calcEMA($closes, $fast);
        $emaSlow = $this->calcEMA($closes, $slow);
        $macd    = $emaFast - $emaSlow;

        // Build MACD series for signal EMA
        $n    = count($closes);
        $macdSeries = [];
        for ($i = $slow - 1; $i < $n; $i++) {
            $slice     = array_slice($closes, 0, $i + 1);
            $macdSeries[] = $this->calcEMA($slice, $fast) - $this->calcEMA($slice, $slow);
        }

        $sig  = $this->calcEMA($macdSeries, $signal);
        $hist = $macd - $sig;

        return [$macd, $sig, $hist];
    }

    public function calcATR(array $highs, array $lows, array $closes, int $period = 14): float
    {
        $n  = count($closes);
        $tr = [];
        for ($i = 1; $i < $n; $i++) {
            $tr[] = max(
                $highs[$i] - $lows[$i],
                abs($highs[$i] - $closes[$i - 1]),
                abs($lows[$i]  - $closes[$i - 1])
            );
        }
        if (empty($tr)) return 0.0;
        return array_sum(array_slice($tr, -$period)) / min($period, count($tr));
    }

    public function calcBB(array $closes, int $period = 20, float $mult = 2.0): array
    {
        $sma  = $this->calcSMA($closes, $period);
        $slice = array_slice($closes, -$period);
        $variance = array_sum(array_map(fn($c) => ($c - $sma) ** 2, $slice)) / $period;
        $std  = sqrt($variance);
        return [$sma + $mult * $std, $sma, $sma - $mult * $std];
    }

    public function calcStoch(array $highs, array $lows, array $closes, int $kP = 14, int $dP = 3, int $smooth = 3): array
    {
        $n  = count($closes);
        $ks = [];
        for ($i = $kP - 1; $i < $n; $i++) {
            $sliceH = array_slice($highs, $i - $kP + 1, $kP);
            $sliceL = array_slice($lows,  $i - $kP + 1, $kP);
            $hh     = max($sliceH);
            $ll     = min($sliceL);
            $ks[]   = $hh == $ll ? 50 : ($closes[$i] - $ll) / ($hh - $ll) * 100;
        }
        if (empty($ks)) return [50.0, 50.0];
        $k = array_sum(array_slice($ks, -$smooth)) / $smooth;
        $d = array_sum(array_slice($ks, -($smooth + $dP - 1), $dP)) / $dP;
        return [$k, $d];
    }

    public function calcCCI(array $highs, array $lows, array $closes, int $period = 20): float
    {
        $n  = count($closes);
        $tp = [];
        for ($i = 0; $i < $n; $i++) {
            $tp[] = ($highs[$i] + $lows[$i] + $closes[$i]) / 3;
        }
        $slice = array_slice($tp, -$period);
        $mean  = array_sum($slice) / $period;
        $mad   = array_sum(array_map(fn($v) => abs($v - $mean), $slice)) / $period;
        return $mad == 0 ? 0 : ($tp[$n - 1] - $mean) / (0.015 * $mad);
    }

    public function calcWilliamsR(array $highs, array $lows, array $closes, int $period = 14): float
    {
        $n  = count($closes);
        $hh = max(array_slice($highs, -$period));
        $ll = min(array_slice($lows,  -$period));
        if ($hh == $ll) return -50.0;
        return ($hh - $closes[$n - 1]) / ($hh - $ll) * -100;
    }

    public function calcADX(array $highs, array $lows, array $closes, int $period = 14): array
    {
        $n    = count($closes);
        $dmP  = $dmM = $tr14 = [];
        for ($i = 1; $i < $n; $i++) {
            $upMove   = $highs[$i] - $highs[$i - 1];
            $downMove = $lows[$i - 1] - $lows[$i];
            $dmP[]    = ($upMove > $downMove && $upMove > 0) ? $upMove : 0;
            $dmM[]    = ($downMove > $upMove && $downMove > 0) ? $downMove : 0;
            $tr14[]   = max($highs[$i] - $lows[$i], abs($highs[$i] - $closes[$i - 1]), abs($lows[$i] - $closes[$i - 1]));
        }
        if (count($dmP) < $period) return [0.0, 0.0, 0.0];

        $atrSum  = array_sum(array_slice($tr14, -$period));
        $diPSum  = array_sum(array_slice($dmP,  -$period));
        $diMSum  = array_sum(array_slice($dmM,  -$period));
        $diPlus  = $atrSum > 0 ? ($diPSum / $atrSum) * 100 : 0;
        $diMinus = $atrSum > 0 ? ($diMSum / $atrSum) * 100 : 0;
        $dx      = ($diPlus + $diMinus) > 0 ? abs($diPlus - $diMinus) / ($diPlus + $diMinus) * 100 : 0;
        return [$dx, $diPlus, $diMinus];
    }

    public function calcOBV(array $closes, array $volumes): float
    {
        $obv = 0.0;
        for ($i = 1; $i < count($closes); $i++) {
            if ($closes[$i] > $closes[$i - 1])      $obv += $volumes[$i];
            elseif ($closes[$i] < $closes[$i - 1])  $obv -= $volumes[$i];
        }
        return $obv;
    }

    public function calcSupertrend(array $highs, array $lows, array $closes, int $period = 10, float $mult = 3.0): array
    {
        $n   = count($closes);
        $atr = $this->calcATR($highs, $lows, $closes, $period);
        $hl2 = ($highs[$n - 1] + $lows[$n - 1]) / 2;
        $upperBand = $hl2 + $mult * $atr;
        $lowerBand = $hl2 - $mult * $atr;
        $direction = $closes[$n - 1] > $lowerBand ? 1 : -1;
        $st        = $direction === 1 ? $lowerBand : $upperBand;
        return [$st, $direction];
    }

    public function calcParabolicSAR(array $highs, array $lows, float $step = 0.02, float $max = 0.2): float
    {
        $n    = count($highs);
        $bull = true;
        $sar  = $lows[0];
        $ep   = $highs[0];
        $af   = $step;

        for ($i = 1; $i < $n; $i++) {
            $sar = $sar + $af * ($ep - $sar);
            if ($bull) {
                if ($lows[$i] < $sar) {
                    $bull = false;
                    $sar  = $ep;
                    $ep   = $lows[$i];
                    $af   = $step;
                } else {
                    if ($highs[$i] > $ep) { $ep = $highs[$i]; $af = min($af + $step, $max); }
                    $sar = min($sar, $lows[$i - 1], $i >= 2 ? $lows[$i - 2] : $lows[$i - 1]);
                }
            } else {
                if ($highs[$i] > $sar) {
                    $bull = true;
                    $sar  = $ep;
                    $ep   = $highs[$i];
                    $af   = $step;
                } else {
                    if ($lows[$i] < $ep) { $ep = $lows[$i]; $af = min($af + $step, $max); }
                    $sar = max($sar, $highs[$i - 1], $i >= 2 ? $highs[$i - 2] : $highs[$i - 1]);
                }
            }
        }
        return $sar;
    }

    public function calcCMF(array $highs, array $lows, array $closes, array $volumes, int $period = 20): float
    {
        $n      = count($closes);
        $mfvSum = $volSum = 0.0;
        $start  = max(0, $n - $period);
        for ($i = $start; $i < $n; $i++) {
            $hl    = $highs[$i] - $lows[$i];
            $mf    = $hl > 0 ? (($closes[$i] - $lows[$i]) - ($highs[$i] - $closes[$i])) / $hl * $volumes[$i] : 0;
            $mfvSum += $mf;
            $volSum += $volumes[$i];
        }
        return $volSum > 0 ? $mfvSum / $volSum : 0.0;
    }

    public function calcMFI(array $highs, array $lows, array $closes, array $volumes, int $period = 14): float
    {
        $n       = count($closes);
        $pos = $neg = 0.0;
        $start   = max(1, $n - $period);
        for ($i = $start; $i < $n; $i++) {
            $tp     = ($highs[$i] + $lows[$i] + $closes[$i]) / 3;
            $tpPrev = ($highs[$i - 1] + $lows[$i - 1] + $closes[$i - 1]) / 3;
            $mf     = $tp * $volumes[$i];
            if ($tp > $tpPrev) $pos += $mf;
            else                $neg += $mf;
        }
        if ($neg == 0) return 100.0;
        return 100 - (100 / (1 + $pos / $neg));
    }

    public function calcROC(array $closes, int $period = 12): float
    {
        $n = count($closes);
        if ($n <= $period) return 0.0;
        $prev = $closes[$n - 1 - $period];
        return $prev > 0 ? ($closes[$n - 1] - $prev) / $prev * 100 : 0.0;
    }

    public function calcVWAP(array $candles): float
    {
        $tpvSum = $volSum = 0.0;
        foreach ($candles as $c) {
            $tp      = ($c['high'] + $c['low'] + $c['close']) / 3;
            $tpvSum += $tp * $c['volume'];
            $volSum += $c['volume'];
        }
        return $volSum > 0 ? $tpvSum / $volSum : 0.0;
    }
}
