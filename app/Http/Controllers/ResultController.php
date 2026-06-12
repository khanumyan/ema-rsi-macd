<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $capital  = (float) ($request->query('capital', 10));
        $leverage = (float) ($request->query('leverage', 20));

        $capital  = max(1, min($capital, 1_000_000));
        $leverage = max(1, min($leverage, 200));

        $rows = $this->buildQuery($capital, $leverage)->get();

        return view('results.index', [
            'rows'     => $rows,
            'capital'  => $capital,
            'leverage' => $leverage,
        ]);
    }

    private function buildQuery(float $capital, float $leverage)
    {
        return DB::table('crypto_sygnals_new')
            ->selectRaw('DATE(signal_time) as day')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'DONE'   AND type = 'BUY'  THEN 1 ELSE 0 END), 0) AS done_buy")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'DONE'   AND type = 'SELL' THEN 1 ELSE 0 END), 0) AS done_sell")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'MISSED' AND type = 'BUY'  THEN 1 ELSE 0 END), 0) AS missed_buy")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'MISSED' AND type = 'SELL' THEN 1 ELSE 0 END), 0) AS missed_sell")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'DONE'   THEN 1 ELSE 0 END), 0) AS total_done")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'MISSED' THEN 1 ELSE 0 END), 0) AS total_missed")
            ->selectRaw("COALESCE(AVG(CASE
                     WHEN status = 'DONE' AND type = 'BUY'  THEN ((take_profit - price)/price)*100
                     WHEN status = 'DONE' AND type = 'SELL' THEN ((price - take_profit)/price)*100
                END), 0) AS avg_take_done")
            ->selectRaw("COALESCE(AVG(CASE
                     WHEN status = 'MISSED' AND type = 'BUY'  THEN ((price - stop_loss)/price)*100
                     WHEN status = 'MISSED' AND type = 'SELL' THEN ((stop_loss - price)/price)*100
                END), 0) AS avg_stop_missed")
            ->selectRaw('
                (
                    COALESCE(SUM(CASE
                        WHEN status = \'DONE\' AND type = \'BUY\'  THEN ? * ? * ((take_profit - price)/price)
                        WHEN status = \'DONE\' AND type = \'SELL\' THEN ? * ? * ((price - take_profit)/price)
                        ELSE 0
                    END), 0)
                    -
                    COALESCE(SUM(CASE
                        WHEN status = \'MISSED\' AND type = \'BUY\'  THEN ? * ? * ((price - stop_loss)/price)
                        WHEN status = \'MISSED\' AND type = \'SELL\' THEN ? * ? * ((stop_loss - price)/price)
                        ELSE 0
                    END), 0)
                ) AS profit
            ', [
                $capital, $leverage,
                $capital, $leverage,
                $capital, $leverage,
                $capital, $leverage,
            ])
            ->whereIn('status', ['DONE', 'MISSED'])
            ->whereNotNull('atr')
            ->whereNotNull('ema')
            ->whereNotNull('macd_histogram')
            ->whereNotNull('take_profit')
            ->where('price', '>', 0)
            ->where('atr', '>', 0)
            ->where(function ($q) {
                $q->where(function ($buy) {
                    $buy->where('type', 'BUY')
                        ->whereRaw('macd < 0')
                        ->whereRaw('macd_histogram > 0')
                        ->whereBetween('rsi', [50, 60])
                        ->whereRaw('(atr / price) * 100 > 3.5')
                        ->whereRaw('ema < ema_slow');
                })
                ->orWhere(function ($sell) {
                    $sell->where('type', 'SELL')
                        ->whereRaw('macd < 0')
                        ->whereRaw('macd_histogram < 0')
                        ->whereBetween('rsi', [35, 40])
                        ->whereRaw('(atr / price) * 100 BETWEEN 0.3 AND 0.6')
                        ->whereRaw('ema > ema_slow');
                });
            })
            ->groupBy(DB::raw('DATE(signal_time)'))
            ->orderBy('day', 'desc');
    }
}

