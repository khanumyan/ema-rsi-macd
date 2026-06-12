<?php

namespace App\Http\Controllers;

use App\Exports\SignalsExport;
use App\Models\CryptoSignal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SignalController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $query = $this->buildBaseQuery($dateFrom, $dateTo);

        $signals = $query->paginate(50)->withQueryString();

        return view('signals.index', [
            'signals'  => $signals,
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }

    public function export(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $query = $this->buildBaseQuery($dateFrom, $dateTo);

        $columns = [
            'signal_time', 'created_at', 'updated_at', 'symbol', 'strategy',
            'type', 'strength', 'price', 'rsi', 'ema', 'ema_slow',
            'macd', 'macd_signal', 'macd_histogram', 'atr',
            'stop_loss', 'take_profit', 'long_score', 'short_score',
            'long_probability', 'short_probability', 'interval', 'limit',
            'volume_ratio', 'htf_trend', 'htf_rsi', 'ltf_rsi',
            'reason', 'sent_to_telegram', 'status',
        ];

        $fileName = 'signals_' . Carbon::now()->format('Y-m-d_H-i') . '.xlsx';

        return Excel::download(new SignalsExport($query, $columns), $fileName);
    }

    private function buildBaseQuery(?string $dateFrom, ?string $dateTo)
    {
        $query = CryptoSignal::query()
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
            ->orderBy('signal_time', 'desc');

        if ($dateFrom) {
            $query->whereDate('signal_time', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('signal_time', '<=', $dateTo);
        }

        return $query;
    }
}

