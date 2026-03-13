<?php

namespace App\Console\Commands;

use App\Models\CryptoSignal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateSignalCandleTimeCommand extends Command
{
    protected $signature = 'signals:update-candle-time
                            {--flow-id= : Flow ID для фильтрации сигналов (опционально)}';

    protected $description = 'Update updated_at field with candle open time when TP or SL was first hit (Binance time + 4 hours)';

    public function handle()
    {
        $startTime = microtime(true);
        $commandStart = Carbon::now();

        $this->info('🕐 Updating signal candle times...');
        Log::info('=== UpdateSignalCandleTime Command Started ===', [
            'started_at' => $commandStart->toDateTimeString(),
            'flow_id' => $this->option('flow-id'),
        ]);

        try {
            // Строим запрос с той же логикой фильтрации
            $matchingSignalsQuery = CryptoSignal::query()
                ->where('created_at', '>=', '2026-03-02 20:00:00')
                ->whereNotNull('atr')
                ->whereNotNull('ema')
                ->whereNotNull('macd_histogram')
                ->whereNotNull('take_profit')
                ->where('price', '>', 0)
                ->where('atr', '>', 0)
                // 📏 TP distance filter: (ABS((price - take_profit) / price) * 100) <= 3
                ->whereRaw('(ABS((price - take_profit) / price) * 100) <= 3')
                ->whereIn('status', ['DONE', 'MISSED']) // Только DONE или MISSED
                ->where(function ($q) {
                    // ================= BUY =================
                    $q->where(function ($buy) {
                        $buy->where('type', 'BUY')
                            // 1️⃣ RSI: rsi > 48 AND rsi < 60
                            ->where('rsi', '>', 48)
                            ->where('rsi', '<', 60)
                            // 2️⃣ MACD histogram / ATR: (macd_histogram / atr) >= 0.25
                            ->whereRaw('(macd_histogram / atr) >= 0.25')
                            // 3️⃣ EMA distance % ATR: (ABS(price - ema) / atr) BETWEEN 0.5 AND 1.5
                            ->whereRaw('(ABS(price - ema) / atr) BETWEEN 0.5 AND 1.5')
                            // 4️⃣ ATR %: ((atr / price) * 100) BETWEEN 0.3 AND 3.0
                            ->whereRaw('((atr / price) * 100) BETWEEN 0.3 AND 3.0')
                            // 5️⃣ Score difference: (long_score - short_score) BETWEEN 10 AND 20
                            ->whereRaw('(long_score - short_score) BETWEEN 10 AND 20');
                    })
                    // ================= SELL =================
                    ->orWhere(function ($sell) {
                        $sell->where('type', 'SELL')
                            // 1️⃣ RSI: rsi BETWEEN 40 AND 52
                            ->whereBetween('rsi', [40, 52])
                            // 2️⃣ MACD histogram / ATR: (ABS(macd_histogram) / atr) >= 0.25
                            ->whereRaw('(ABS(macd_histogram) / atr) >= 0.25')
                            // 3️⃣ EMA distance % ATR: (ABS(price - ema) / atr) BETWEEN 0.5 AND 1.5
                            ->whereRaw('(ABS(price - ema) / atr) BETWEEN 0.5 AND 1.5')
                            // 4️⃣ ATR %: ((atr / price) * 100) BETWEEN 0.3 AND 3.0
                            ->whereRaw('((atr / price) * 100) BETWEEN 0.3 AND 3.0')
                            // 5️⃣ Score difference: (short_score - long_score) BETWEEN 10 AND 20
                            ->whereRaw('(short_score - long_score) BETWEEN 10 AND 20');
                    });
                });

            // Если указан flow_id, добавляем фильтр
            if ($this->option('flow-id')) {
                $matchingSignalsQuery->where('flow_id', $this->option('flow-id'));
            }

            $signals = $matchingSignalsQuery->get();

            if ($signals->isEmpty()) {
                $this->info('✅ No signals found matching criteria');
                Log::info('UpdateSignalCandleTime: No signals found', [
                    'flow_id' => $this->option('flow-id'),
                ]);
                return Command::SUCCESS;
            }

            $this->info("📊 Found {$signals->count()} signals to process");
            Log::info('UpdateSignalCandleTime: Signals found', [
                'count' => $signals->count(),
                'flow_id' => $this->option('flow-id'),
                'symbols' => $signals->pluck('symbol')->unique()->values()->toArray(),
            ]);

            $updatedCount = 0;
            $errorCount = 0;

            $progressBar = $this->output->createProgressBar($signals->count());
            $progressBar->start();

            foreach ($signals as $signal) {
                try {
                    Log::debug("UpdateSignalCandleTime: Processing signal", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'type' => $signal->type,
                        'status' => $signal->status,
                        'signal_time' => $signal->signal_time?->toDateTimeString(),
                        'created_at' => $signal->created_at->toDateTimeString(),
                    ]);

                    $candleTime = $this->findFirstHitCandleTime($signal);

                    if ($candleTime) {
                        // Время от Binance + 4 часа
                        $updatedAt = Carbon::createFromTimestamp($candleTime / 1000)->addHours(4);

                        $signal->update(['updated_at' => $updatedAt]);

                        Log::info("UpdateSignalCandleTime: Signal updated", [
                            'signal_id' => $signal->id,
                            'symbol' => $signal->symbol,
                            'candle_open_time_ms' => $candleTime,
                            'candle_open_time' => Carbon::createFromTimestamp($candleTime / 1000)->toDateTimeString(),
                            'updated_at' => $updatedAt->toDateTimeString(),
                        ]);

                        $updatedCount++;
                    } else {
                        Log::warning("UpdateSignalCandleTime: Could not find candle time", [
                            'signal_id' => $signal->id,
                            'symbol' => $signal->symbol,
                        ]);
                    }

                    $progressBar->advance();
                    usleep(200000); // 0.2 секунды задержка между запросами
                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMsg = "Error processing signal {$signal->id}: " . $e->getMessage();
                    Log::error("UpdateSignalCandleTime: Exception processing signal", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'type' => $signal->type,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            $commandEnd = Carbon::now();

            $this->info("✅ Update complete!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Updated', $updatedCount],
                    ['Errors', $errorCount],
                    ['Total', $signals->count()],
                ]
            );

            $this->info("⏱️  Execution time: {$executionTime} sec");

            Log::info('=== UpdateSignalCandleTime Command Completed ===', [
                'ended_at' => $commandEnd->toDateTimeString(),
                'execution_time_seconds' => $executionTime,
                'total_signals' => $signals->count(),
                'updated_count' => $updatedCount,
                'error_count' => $errorCount,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $errorMsg = '❌ Error: ' . $e->getMessage();
            $this->error($errorMsg);
            Log::error('UpdateSignalCandleTime: Command failed with exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Находит время открытия первой свечи, когда цена достигла TP или SL
     * Возвращает время открытия свечи в миллисекундах или null
     */
    private function findFirstHitCandleTime(CryptoSignal $signal): ?int
    {
        // Определяем время начала проверки (signal_time или created_at)
        $signalTime =  $signal->created_at;

        // Получаем исторические данные от времени открытия сигнала до сейчас
        $startTime = $signalTime->timestamp * 1000; // Binance требует миллисекунды
        $endTime = Carbon::now()->addHours(4)->timestamp * 1000;

        Log::debug("UpdateSignalCandleTime: Fetching historical data", [
            'signal_id' => $signal->id,
            'symbol' => $signal->symbol,
            'signal_time' => $signalTime->toDateTimeString(),
            'start_time_ms' => $startTime,
            'end_time_ms' => $endTime,
        ]);

        // Получаем все свечи за период (используем интервал из сигнала или 15m по умолчанию)
        $interval = $signal->interval ?? '15m';
        $allKlines = $this->fetchHistoricalKlines($signal->symbol, $interval, $startTime, $endTime);

        if (empty($allKlines)) {
            Log::warning("UpdateSignalCandleTime: No klines data received", [
                'signal_id' => $signal->id,
                'symbol' => $signal->symbol,
            ]);
            return null;
        }

        Log::debug("UpdateSignalCandleTime: Klines received", [
            'signal_id' => $signal->id,
            'symbol' => $signal->symbol,
            'klines_count' => count($allKlines),
        ]);

        // Проверяем свечи в хронологическом порядке (от времени сигнала вперед)
        foreach ($allKlines as $kline) {
            // Формат свечи: [0]=openTime(ms), [1]=open, [2]=high, [3]=low, [4]=close, [5]=volume, [6]=closeTime(ms), ...]
            $klineOpenTime = (int) $kline[0]; // Время открытия свечи в миллисекундах
            $klineCloseTime = (int) $kline[6]; // Время закрытия свечи в миллисекундах
            $high = (float) $kline[2]; // High price (максимум цены внутри свечи)
            $low = (float) $kline[3];  // Low price (минимум цены внутри свечи)

            // Пропускаем свечи, которые закрылись ДО создания сигнала
            if ($klineCloseTime < $startTime) {
                continue;
            }

            if ($signal->type === 'BUY') {
                // BUY: SL ниже entry, TP выше entry
                // Проверяем high/low свечи: цена упала до/ниже SL (low <= stop_loss) или поднялась до/выше TP (high >= take_profit)

                $hitSL = $low <= (float) $signal->stop_loss;
                $hitTP = $high >= (float) $signal->take_profit;

                if ($hitSL && $hitTP) {
                    // Оба события в одной свече - определяем что было раньше
                    // Если low коснулся/пробил SL, значит цена сначала упала = SL сработал первым = MISSED
                    // Но нам нужно время открытия свечи в любом случае
                    Log::info("UpdateSignalCandleTime: BUY signal - both SL and TP hit", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'low' => $low,
                        'high' => $high,
                        'stop_loss' => $signal->stop_loss,
                        'take_profit' => $signal->take_profit,
                    ]);
                    return $klineOpenTime;
                } elseif ($hitSL) {
                    // Сначала коснулся SL (low <= stop_loss) - MISSED
                    Log::info("UpdateSignalCandleTime: BUY signal - SL hit", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'low' => $low,
                        'stop_loss' => $signal->stop_loss,
                    ]);
                    return $klineOpenTime;
                } elseif ($hitTP) {
                    // Сначала коснулся TP (high >= take_profit) - DONE
                    Log::info("UpdateSignalCandleTime: BUY signal - TP hit", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'high' => $high,
                        'take_profit' => $signal->take_profit,
                    ]);
                    return $klineOpenTime;
                }
            } else {
                // SELL: SL выше entry, TP ниже entry
                // Проверяем high/low свечи: цена поднялась до/выше SL (high >= stop_loss) или упала до/ниже TP (low <= take_profit)

                $hitSL = $high >= (float) $signal->stop_loss;
                $hitTP = $low <= (float) $signal->take_profit;

                if ($hitSL && $hitTP) {
                    // Оба события в одной свече - определяем что было раньше
                    // Если high коснулся/пробил SL, значит цена сначала поднялась = SL сработал первым = MISSED
                    // Но нам нужно время открытия свечи в любом случае
                    Log::info("UpdateSignalCandleTime: SELL signal - both SL and TP hit", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'low' => $low,
                        'high' => $high,
                        'stop_loss' => $signal->stop_loss,
                        'take_profit' => $signal->take_profit,
                    ]);
                    return $klineOpenTime;
                } elseif ($hitSL) {
                    // Сначала коснулся SL (high >= stop_loss) - MISSED
                    Log::info("UpdateSignalCandleTime: SELL signal - SL hit", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'high' => $high,
                        'stop_loss' => $signal->stop_loss,
                    ]);
                    return $klineOpenTime;
                } elseif ($hitTP) {
                    // Сначала коснулся TP (low <= take_profit) - DONE
                    Log::info("UpdateSignalCandleTime: SELL signal - TP hit", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'low' => $low,
                        'take_profit' => $signal->take_profit,
                    ]);
                    return $klineOpenTime;
                }
            }
        }

        // Если прошли все свечи и не достигли ни TP, ни SL - не должно быть такого для DONE/MISSED
        Log::warning("UpdateSignalCandleTime: No TP/SL hit found for DONE/MISSED signal", [
            'signal_id' => $signal->id,
            'symbol' => $signal->symbol,
            'status' => $signal->status,
            'klines_checked' => count($allKlines),
        ]);
        return null;
    }

    /**
     * Получает исторические данные klines за период
     * Binance API формат: [openTime, open, high, low, close, volume, closeTime, ...]
     */
    private function fetchHistoricalKlines(string $symbol, string $interval, int $startTime, int $endTime): array
    {
        try {
            $allKlines = [];
            $currentStartTime = $startTime;
            $limit = 1000; // Максимум за один запрос (Binance лимит)

            // Нормализация symbol (если уже содержит USDT, не добавляем)
            $normalizedSymbol = str_ends_with(strtoupper($symbol), 'USDT')
                ? strtoupper($symbol)
                : strtoupper($symbol) . 'USDT';

            // Binance API позволяет получить максимум 1000 свечей за запрос
            // Нужно делать несколько запросов если период большой
            $requestCount = 0;
            while ($currentStartTime < $endTime) {
                $requestCount++;
                Log::debug("UpdateSignalCandleTime: Fetching klines (request #{$requestCount})", [
                    'symbol' => $normalizedSymbol,
                    'interval' => $interval,
                    'start_time' => $currentStartTime,
                    'end_time' => $endTime,
                ]);

                $response = Http::timeout(30)->get('https://fapi.binance.com/fapi/v1/klines', [
                    'symbol' => $normalizedSymbol,
                    'interval' => $interval,
                    'startTime' => $currentStartTime,
                    'endTime' => $endTime,
                    'limit' => $limit
                ]);

                if (!$response->successful()) {
                    Log::warning("UpdateSignalCandleTime: Failed to fetch historical klines", [
                        'symbol' => $normalizedSymbol,
                        'http_status' => $response->status(),
                        'response_body' => $response->body(),
                        'request_number' => $requestCount,
                    ]);
                    break;
                }

                $klines = $response->json();
                if (empty($klines) || !is_array($klines)) {
                    Log::debug("UpdateSignalCandleTime: Empty or invalid klines response", [
                        'symbol' => $normalizedSymbol,
                        'request_number' => $requestCount,
                    ]);
                    break;
                }

                Log::debug("UpdateSignalCandleTime: Klines received", [
                    'symbol' => $normalizedSymbol,
                    'klines_count' => count($klines),
                    'request_number' => $requestCount,
                ]);

                // Добавляем только свечи, которые попадают в наш период
                foreach ($klines as $kline) {
                    $klineTime = (int) $kline[0];
                    if ($klineTime >= $startTime && $klineTime <= $endTime) {
                        $allKlines[] = $kline;
                    }
                }

                // Если получили меньше лимита, значит это последняя порция
                if (count($klines) < $limit) {
                    break;
                }

                // Следующий запрос начинаем с времени закрытия последней свечи + 1ms
                $lastKlineCloseTime = (int) $klines[count($klines) - 1][6]; // closeTime в миллисекундах
                $currentStartTime = $lastKlineCloseTime + 1;

                // Если последняя свеча уже после endTime, прекращаем
                if ($lastKlineCloseTime >= $endTime) {
                    break;
                }

                usleep(100000); // 0.1 секунды задержка между запросами (rate limit)
            }

            // Сортируем по времени открытия (на случай если были дубликаты)
            usort($allKlines, fn($a, $b) => $a[0] <=> $b[0]);

            return $allKlines;

        } catch (\Exception $e) {
            Log::error("UpdateSignalCandleTime: Exception fetching historical klines", [
                'symbol' => $symbol,
                'normalized_symbol' => $normalizedSymbol ?? 'unknown',
                'interval' => $interval,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }
}
