<?php

namespace App\Http\Controllers;

use App\Models\CryptoSignal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SignalController extends Controller
{
    private const EXCLUDED_SYMBOLS = [
        'CELO', 'STRK', '4', 'SIGN', 'USDC', 'TA', 'KOMA', 'BTC',
        'RLS', 'LAB', 'AGT', 'SKY', 'STG', 'G', 'LINEA', 'SCRT',
        'ARK', 'XPIN', 'ESPORTS', 'API3', 'HBAR',
    ];

    public function index(Request $request)
    {
        $filter = $request->query('filter', 'strong'); // strong | weak
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = $this->buildBaseQuery($filter, $dateFrom, $dateTo);

        $signals = $query->paginate(50)->withQueryString();

        return view('signals.index', [
            'signals' => $signals,
            'filter' => $filter,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filter = $request->query('filter', 'strong');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = $this->buildBaseQuery($filter, $dateFrom, $dateTo);

        $columns = [
            'signal_time',
            'created_at',
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
            'long_score',
            'short_score',
            'long_probability',
            'short_probability',
            'interval',
            'limit',
            'volume_ratio',
            'htf_trend',
            'htf_rsi',
            'ltf_rsi',
            'reason',
            'sent_to_telegram',
            'status',
        ];

        $suffix = $filter === 'weak' ? 'weak' : 'strong';
        $datePart = Carbon::now()->format('Y-m-d_H-i');
        $fileName = "signals_{$suffix}_{$datePart}.csv";

        $callback = static function () use ($query, $columns) {
            $handle = fopen('php://output', 'w');

            // Headers row
            fputcsv($handle, $columns);

            $query->chunk(500, function ($chunk) use ($handle, $columns) {
                foreach ($chunk as $signal) {
                    $row = [];
                    foreach ($columns as $column) {
                        $value = $signal->{$column};
                        if ($value instanceof Carbon) {
                            $value = $value->toDateTimeString();
                        }
                        $row[] = $value;
                    }
                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload(
            $callback,
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        );
    }

    private function buildBaseQuery(string $filter, ?string $dateFrom, ?string $dateTo)
    {
        $base = CryptoSignal::query()
            ->whereNotNull('atr')
            ->whereNotNull('ema')
            ->whereNotNull('macd_histogram')
            ->whereNotNull('take_profit')
            ->where('price', '>', 0)
            ->where('atr', '>', 0)
            ->whereRaw('(ABS((price - take_profit) / price) * 100) <= 3')
            ->whereNotIn('symbol', self::EXCLUDED_SYMBOLS);

        if ($dateFrom) {
            $base->whereDate('signal_time', '>=', $dateFrom);
        }

        if ($dateTo) {
            $base->whereDate('signal_time', '<=', $dateTo);
        }

        if ($filter === 'weak') {
            $base->where(function ($q) {
                // Weak BUY
                $q->where(function ($buy) {
                    $buy->where('type', 'BUY')
                        ->whereBetween('rsi', [48, 60])
                        ->whereRaw('(macd_histogram / atr) >= 0.25')
                        ->whereRaw('(ABS(price - ema) / atr) BETWEEN 0.5 AND 1.5')
                        ->whereRaw('((atr / price) * 100) BETWEEN 0.3 AND 3.0')
                        ->whereRaw('(long_score - short_score) BETWEEN 10 AND 20');
                })
                // Weak SELL
                ->orWhere(function ($sell) {
                    $sell->where('type', 'SELL')
                        ->whereBetween('rsi', [40, 52])
                        ->whereRaw('(ABS(macd_histogram) / atr) >= 0.25')
                        ->whereRaw('(ABS(price - ema) / atr) BETWEEN 0.5 AND 2')
                        ->whereRaw('((atr / price) * 100) BETWEEN 0.3 AND 3.0')
                        ->whereRaw('(short_score - long_score) BETWEEN 10 AND 20');
                });
            })
            ->orderBy('signal_time', 'asc');
        } else {
            $base->where(function ($q) {
                // Strong BUY
                $q->where(function ($buy) {
                    $buy->where('type', 'BUY')
                        ->whereBetween('rsi', [55, 70])
                        ->whereRaw('(macd_histogram / atr) >= 0.4')
                        ->whereRaw('(ABS(price - ema) / atr) BETWEEN 1.0 AND 1.8')
                        ->whereRaw('((atr / price) * 100) BETWEEN 0.6 AND 2.0')
                        ->whereRaw('(long_score - short_score) BETWEEN 10 AND 20');
                })
                // Strong SELL
                ->orWhere(function ($sell) {
                    $sell->where('type', 'SELL')
                        ->whereBetween('rsi', [35, 48])
                        ->whereRaw('(ABS(macd_histogram) / atr) >= 0.35')
                        ->whereRaw('(ABS(price - ema) / atr) BETWEEN 0.8 AND 1.5')
                        ->whereRaw('((atr / price) * 100) BETWEEN 0.5 AND 2.5')
                        ->whereRaw('(short_score - long_score) BETWEEN 10 AND 20');
                });
            })
            ->orderBy('signal_time', 'desc');
        }

        return $base;
    }
}

