<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    private const EXCLUDED_SYMBOLS = [
        'CELO', 'STRK', '4', 'SIGN', 'USDC', 'TA', 'KOMA', 'BTC',
        'RLS', 'LAB', 'AGT', 'SKY', 'STG', 'G', 'LINEA', 'SCRT',
        'ARK', 'XPIN', 'ESPORTS', 'API3', 'HBAR',
    ];

    public function index(Request $request)
    {
        $filter = $request->query('filter', 'strong'); // strong | weak

        $capital = (float) ($request->query('capital', 10));
        $leverage = (float) ($request->query('leverage', 20));

        // Безопасные пределы
        $capital = max(1, min($capital, 1_000_000));
        $leverage = max(1, min($leverage, 200));

        $rows = $this->buildQuery($filter, $capital, $leverage)->get();

        return view('results.index', [
            'rows' => $rows,
            'filter' => $filter,
            'capital' => $capital,
            'leverage' => $leverage,
        ]);
    }

    private function buildQuery(string $filter, float $capital, float $leverage)
    {
        $base = DB::table('crypto_signals')
            ->selectRaw('DATE(signal_time) as day')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'done'   AND type = 'BUY'  THEN 1 ELSE 0 END), 0) AS done_buy")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'done'   AND type = 'SELL' THEN 1 ELSE 0 END), 0) AS done_sell")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'missed' AND type = 'BUY'  THEN 1 ELSE 0 END), 0) AS missed_buy")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'missed' AND type = 'SELL' THEN 1 ELSE 0 END), 0) AS missed_sell")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'done'   THEN 1 ELSE 0 END), 0) AS total_done")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END), 0) AS total_missed")
            ->selectRaw("COALESCE(AVG(CASE
                     WHEN status = 'done' AND type = 'BUY'  THEN ((take_profit - price)/price)*100
                     WHEN status = 'done' AND type = 'SELL' THEN ((price - take_profit)/price)*100
                END), 0) AS avg_take_done")
            ->selectRaw("COALESCE(AVG(CASE
                     WHEN status = 'missed' AND type = 'BUY'  THEN ((price - stop_loss)/price)*100
                     WHEN status = 'missed' AND type = 'SELL' THEN ((stop_loss - price)/price)*100
                END), 0) AS avg_stop_missed")
            ->selectRaw('
                (
                    COALESCE(SUM(CASE
                        WHEN status = \'done\' AND type = \'BUY\'  THEN ? * ? * ((take_profit - price)/price)
                        WHEN status = \'done\' AND type = \'SELL\' THEN ? * ? * ((price - take_profit)/price)
                        ELSE 0
                    END), 0)
                    -
                    COALESCE(SUM(CASE
                        WHEN status = \'missed\' AND type = \'BUY\'  THEN ? * ? * ((price - stop_loss)/price)
                        WHEN status = \'missed\' AND type = \'SELL\' THEN ? * ? * ((stop_loss - price)/price)
                        ELSE 0
                    END), 0)
                ) AS profit
            ', [
                $capital, $leverage,
                $capital, $leverage,
                $capital, $leverage,
                $capital, $leverage,
            ])
            ->whereNotNull('status')
            ->whereNotNull('atr')
            ->whereNotNull('ema')
            ->whereNotNull('macd_histogram')
            ->whereNotNull('take_profit')
            ->where('price', '>', 0)
            ->where('atr', '>', 0)
            ->whereRaw('(ABS((price - take_profit) / price) * 100) <= 3')
            ->whereNotIn('symbol', self::EXCLUDED_SYMBOLS);

        if ($filter === 'weak') {
            $base->where(function ($q) {
                $q->where(function ($buy) {
                    $buy->where('type', 'BUY')
                        ->whereBetween('rsi', [48, 60])
                        ->whereRaw('(macd_histogram / atr) >= 0.25')
                        ->whereRaw('(ABS(price - ema) / atr) BETWEEN 0.5 AND 1.5')
                        ->whereRaw('((atr / price) * 100) BETWEEN 0.3 AND 3.0')
                        ->whereRaw('(long_score - short_score) BETWEEN 10 AND 20');
                })
                ->orWhere(function ($sell) {
                    $sell->where('type', 'SELL')
                        ->whereBetween('rsi', [40, 52])
                        ->whereRaw('(ABS(macd_histogram) / atr) >= 0.25')
                        ->whereRaw('(ABS(price - ema) / atr) BETWEEN 0.5 AND 2')
                        ->whereRaw('((atr / price) * 100) BETWEEN 0.3 AND 3.0')
                        ->whereRaw('(short_score - long_score) BETWEEN 10 AND 20');
                });
            });
        } else {
            $base->where(function ($q) {
                $q->where(function ($buy) {
                    $buy->where('type', 'BUY')
                        ->whereBetween('rsi', [55, 70])
                        ->whereRaw('(macd_histogram / atr) >= 0.4')
                        ->whereRaw('(ABS(price - ema) / atr) BETWEEN 1.0 AND 1.8')
                        ->whereRaw('((atr / price) * 100) BETWEEN 0.6 AND 2.0')
                        ->whereRaw('(long_score - short_score) BETWEEN 10 AND 20');
                })
                ->orWhere(function ($sell) {
                    $sell->where('type', 'SELL')
                        ->whereBetween('rsi', [35, 48])
                        ->whereRaw('(ABS(macd_histogram) / atr) >= 0.35')
                        ->whereRaw('(ABS(price - ema) / atr) BETWEEN 0.8 AND 1.5')
                        ->whereRaw('((atr / price) * 100) BETWEEN 0.5 AND 2.5')
                        ->whereRaw('(short_score - long_score) BETWEEN 10 AND 20');
                });
            });
        }

        return $base
            ->groupBy(DB::raw('DATE(signal_time)'))
            ->orderBy('day', 'desc');
    }
}

