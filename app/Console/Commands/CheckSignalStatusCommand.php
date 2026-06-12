<?php

namespace App\Console\Commands;

use App\Models\CryptoSignal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckSignalStatusCommand extends Command
{
    protected $signature = 'signals:check-status
                            {--hours=12 : Количество часов назад для проверки (по умолчанию 12)}
                            {--range=24 : Диапазон проверки в часах (по умолчанию 24)}';

    protected $description = 'Check status of signals (DONE/MISSED/PROCESSING) based on historical price data';

    public function handle()
    {
        $startTime = microtime(true);
        $commandStart = Carbon::now();

        $this->info('🔍 Checking signal statuses...');
        Log::info('=== CheckSignalStatus Command Started ===', [
            'started_at' => $commandStart->toDateTimeString(),
            'options' => [
                'hours' => $this->option('hours'),
                'range' => $this->option('range'),
            ]
        ]);

        try {
            // Находим сигналы, которые нужно проверить
            // По умолчанию проверяем сигналы от 12 до 36 часов назад (12 + 24 = 36)
//            $hoursAgo = (int) $this->option('hours');
//            $rangeHours = (int) $this->option('range');
//
//            $now = Carbon::now();
//            $fromTime = $now->copy()->subHours($hoursAgo + $rangeHours);
//            $toTime = $now->copy()->subHours($hoursAgo);

//            $this->info("📅 Checking signals from {$fromTime->format('Y-m-d H:i:s')} to {$toTime->format('Y-m-d H:i:s')}");
//            Log::info('CheckSignalStatus: Time range', [
//                'from' => $fromTime->toDateTimeString(),
//                'to' => $toTime->toDateTimeString(),
//                'hours_ago' => $hoursAgo,
//                'range_hours' => $rangeHours,
//            ]);

            // Ищем сигналы без статуса или со статусом PROCESSING в указанном диапазоне
            // Используем signal_time если есть, иначе created_at
            $signals = CryptoSignal::where(function ($query) {
                $query->whereNull('status')
                      ->orWhere('status', 'PROCESSING');
            })
//            ->where(function ($query) use ($fromTime, $toTime) {
//                $query->where(function ($q) use ($fromTime, $toTime) {
//                    // Если есть signal_time, используем его
//                    $q->whereNotNull('signal_time')
//                      ->whereBetween('signal_time', [$fromTime, $toTime]);
//                })->orWhere(function ($q) use ($fromTime, $toTime) {
//                    // Иначе используем created_at
//                    $q->whereNull('signal_time')
//                      ->whereBetween('created_at', [$fromTime, $toTime]);
//                });
//            })
            ->whereIn('type', ['BUY', 'SELL']) // Только BUY и SELL сигналы (не HOLD)
            ->whereNotNull('stop_loss')
            ->whereNotNull('take_profit')
//            ->where('sent_to_telegram', 1)
        ->whereNull('flow_id')
            ->get();

            if ($signals->isEmpty()) {
                $this->info('✅ No signals found in the specified time range');
//                Log::info('CheckSignalStatus: No signals found', [
//                    'from' => $fromTime->toDateTimeString(),
//                    'to' => $toTime->toDateTimeString(),
//                ]);
                return Command::SUCCESS;
            }

            $this->info("📊 Found {$signals->count()} signals to check");
            Log::info('CheckSignalStatus: Signals found', [
                'count' => $signals->count(),
                'symbols' => $signals->pluck('symbol')->unique()->values()->toArray(),
            ]);

            $doneCount = 0;
            $missedCount = 0;
            $processingCount = 0;
            $errorCount = 0;

            $progressBar = $this->output->createProgressBar($signals->count());
            $progressBar->start();

            foreach ($signals as $signal) {
                try {
                    Log::debug("CheckSignalStatus: Checking signal", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'type' => $signal->type,
                        'signal_time' => $signal->signal_time?->toDateTimeString(),
                        'created_at' => $signal->created_at->toDateTimeString(),
                    ]);

                    $status = $this->checkSignalStatus($signal);
                    $signal->update(['status' => $status]);

                    Log::info("CheckSignalStatus: Signal status updated", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'old_status' => $signal->getOriginal('status'),
                        'new_status' => $status,
                    ]);

                    match($status) {
                        'DONE' => $doneCount++,
                        'MISSED' => $missedCount++,
                        'PROCESSING' => $processingCount++,
                        default => null
                    };

                    $progressBar->advance();
                    usleep(200000); // 0.2 секунды задержка между запросами
                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMsg = "Error checking signal {$signal->id}: " . $e->getMessage();
                    Log::error("CheckSignalStatus: Exception checking signal", [
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

            $this->info("✅ Status check complete!");
            $this->table(
                ['Status', 'Count'],
                [
                    ['DONE', $doneCount],
                    ['MISSED', $missedCount],
                    ['PROCESSING', $processingCount],
                    ['ERRORS', $errorCount],
                ]
            );

            $this->info("⏱️  Время выполнения: {$executionTime} сек");

            Log::info('=== CheckSignalStatus Command Completed ===', [
                'ended_at' => $commandEnd->toDateTimeString(),
                'execution_time_seconds' => $executionTime,
                'total_signals' => $signals->count(),
                'done_count' => $doneCount,
                'missed_count' => $missedCount,
                'processing_count' => $processingCount,
                'error_count' => $errorCount,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $errorMsg = '❌ Error: ' . $e->getMessage();
            $this->error($errorMsg);
            Log::error('CheckSignalStatus: Command failed with exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Проверяет статус сигнала на основе исторических данных
     * Проверяет свечи от времени создания сигнала до текущего момента в хронологическом порядке
     * Формат свечи от Binance: [openTime, open, high, low, close, volume, closeTime, ...]
     */
    private function checkSignalStatus(CryptoSignal $signal): string
    {
        // Определяем время начала проверки (signal_time или created_at)
        $signalTime = $signal->created_at;

        // Получаем исторические данные от времени открытия сигнала до сейчас
        $startTime = $signalTime->timestamp * 1000; // Binance требует миллисекунды
        $endTime = Carbon::now()->addHours(4)->timestamp * 1000;

        Log::debug("CheckSignalStatus: Fetching historical data", [
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
            Log::warning("CheckSignalStatus: No klines data received", [
                'signal_id' => $signal->id,
                'symbol' => $signal->symbol,
            ]);
            return 'PROCESSING'; // Если не удалось получить данные, оставляем в обработке
        }

        Log::debug("CheckSignalStatus: Klines received", [
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

            // Если свеча открылась ДО сигнала, но закрылась ПОСЛЕ, то мы проверяем high/low
            // но понимаем, что цена могла достичь этих уровней в любой момент между openTime и closeTime
            // Однако, если свеча закрылась после сигнала, то часть её жизни (от startTime до closeTime)
            // точно относится к периоду после сигнала

            if ($signal->type === 'BUY') {
                // BUY: SL ниже entry, TP выше entry
                // Проверяем high/low свечи: цена упала до/ниже SL (low <= stop_loss) или поднялась до/выше TP (high >= take_profit)

                $hitSL = $low <= (float) $signal->stop_loss;
                $hitTP = $high >= (float) $signal->take_profit;

                if ($hitSL && $hitTP) {
                    // Оба события в одной свече - определяем что было раньше
                    // Если low коснулся/пробил SL, значит цена сначала упала = SL сработал первым = MISSED
                    Log::info("CheckSignalStatus: BUY signal - both SL and TP hit", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'low' => $low,
                        'high' => $high,
                        'stop_loss' => $signal->stop_loss,
                        'take_profit' => $signal->take_profit,
                    ]);
                    return 'MISSED';
                } elseif ($hitSL) {
                    // Сначала коснулся SL (low <= stop_loss) - MISSED
                    Log::info("CheckSignalStatus: BUY signal - SL hit", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'low' => $low,
                        'stop_loss' => $signal->stop_loss,
                    ]);
                    return 'MISSED';
                } elseif ($hitTP) {
                    // Сначала коснулся TP (high >= take_profit) - DONE
                    Log::info("CheckSignalStatus: BUY signal - TP hit", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'high' => $high,
                        'take_profit' => $signal->take_profit,
                    ]);
                    return 'DONE';
                }
            } else {
                // SELL: SL выше entry, TP ниже entry
                // Проверяем high/low свечи: цена поднялась до/выше SL (high >= stop_loss) или упала до/ниже TP (low <= take_profit)

                $hitSL = $high >= (float) $signal->stop_loss;
                $hitTP = $low <= (float) $signal->take_profit;

                if ($hitSL && $hitTP) {
                    // Оба события в одной свече - определяем что было раньше
                    // Если high коснулся/пробил SL, значит цена сначала поднялась = SL сработал первым = MISSED
                    Log::info("CheckSignalStatus: SELL signal - both SL and TP hit", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'low' => $low,
                        'high' => $high,
                        'stop_loss' => $signal->stop_loss,
                        'take_profit' => $signal->take_profit,
                    ]);
                    return 'MISSED';
                } elseif ($hitSL) {
                    // Сначала коснулся SL (high >= stop_loss) - MISSED
                    Log::info("CheckSignalStatus: SELL signal - SL hit", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'high' => $high,
                        'stop_loss' => $signal->stop_loss,
                    ]);
                    return 'MISSED';
                } elseif ($hitTP) {
                    // Сначала коснулся TP (low <= take_profit) - DONE
                    Log::info("CheckSignalStatus: SELL signal - TP hit", [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'low' => $low,
                        'take_profit' => $signal->take_profit,
                    ]);
                    return 'DONE';
                }
            }
        }

        // Если прошли все свечи и не достигли ни TP, ни SL - все еще в процессе
        Log::debug("CheckSignalStatus: No TP/SL hit, still processing", [
            'signal_id' => $signal->id,
            'symbol' => $signal->symbol,
            'klines_checked' => count($allKlines),
        ]);
        return 'PROCESSING';
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
                Log::debug("CheckSignalStatus: Fetching klines (request #{$requestCount})", [
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
                    Log::warning("CheckSignalStatus: Failed to fetch historical klines", [
                        'symbol' => $normalizedSymbol,
                        'http_status' => $response->status(),
                        'response_body' => $response->body(),
                        'request_number' => $requestCount,
                    ]);
                    break;
                }

                $klines = $response->json();
                if (empty($klines) || !is_array($klines)) {
                    Log::debug("CheckSignalStatus: Empty or invalid klines response", [
                        'symbol' => $normalizedSymbol,
                        'request_number' => $requestCount,
                    ]);
                    break;
                }

                Log::debug("CheckSignalStatus: Klines received", [
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
            Log::error("CheckSignalStatus: Exception fetching historical klines", [
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

