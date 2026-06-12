<?php

namespace App\Console\Commands;

use App\Models\CryptoSignalNew;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Та же логика, что CheckSignalStatusCommand, но выборка и обновление из таблицы crypto_sygnals_new.
 *
 * По умолчанию без whereNull('flow_id') — строки из бэктеста имеют flow_id.
 * Флаг --only-without-flow повторяет фильтр оригинальной команды (только flow_id IS NULL).
 */
class CheckSignalStatusNewCommand extends Command
{
    protected $signature = 'signals:check-status-new
                            {--hours=12 : Количество часов назад для проверки (по умолчанию 12)}
                            {--range=24 : Диапазон проверки в часах (по умолчанию 24)}
                            {--only-without-flow : Как в signals:check-status — только строки с flow_id NULL}';

    protected $description = 'Check status of signals in crypto_sygnals_new (DONE/MISSED/PROCESSING) based on historical price data';

    public function handle()
    {
        $startTime = microtime(true);
        $commandStart = Carbon::now();

        $this->info('🔍 Checking signal statuses (crypto_sygnals_new)...');
        Log::info('=== CheckSignalStatusNew Command Started ===', [
            'started_at' => $commandStart->toDateTimeString(),
            'options' => [
                'hours' => $this->option('hours'),
                'range' => $this->option('range'),
                'only_without_flow' => (bool) $this->option('only-without-flow'),
            ],
        ]);

        try {
            $signalsQuery = CryptoSignalNew::where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'PROCESSING');
            })
                ->whereIn('type', ['BUY', 'SELL'])
                ->whereNotNull('stop_loss')
                ->where('sent_to_telegram', 1)
                ->whereNotNull('take_profit');

            if ($this->option('only-without-flow')) {
                $signalsQuery->whereNull('flow_id');
            }

            $signals = $signalsQuery->get();

            if ($signals->isEmpty()) {
                $this->info('✅ No signals found in the specified time range');

                return Command::SUCCESS;
            }

            $this->info("📊 Found {$signals->count()} signals to check");
            Log::info('CheckSignalStatusNew: Signals found', [
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
                    Log::debug('CheckSignalStatusNew: Checking signal', [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'type' => $signal->type,
                        'signal_time' => $signal->signal_time?->toDateTimeString(),
                        'created_at' => $signal->created_at->toDateTimeString(),
                    ]);

                    $status = $this->checkSignalStatus($signal);
                    $signal->update(['status' => $status]);

                    Log::info('CheckSignalStatusNew: Signal status updated', [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'old_status' => $signal->getOriginal('status'),
                        'new_status' => $status,
                    ]);

                    match ($status) {
                        'DONE' => $doneCount++,
                        'MISSED' => $missedCount++,
                        'PROCESSING' => $processingCount++,
                        default => null,
                    };

                    $progressBar->advance();
                    usleep(200000);
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('CheckSignalStatusNew: Exception checking signal', [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'type' => $signal->type,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            $executionTime = round(microtime(true) - $startTime, 2);
            $commandEnd = Carbon::now();

            $this->info('✅ Status check complete!');
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

            Log::info('=== CheckSignalStatusNew Command Completed ===', [
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
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('CheckSignalStatusNew: Command failed with exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    private function checkSignalStatus(CryptoSignalNew $signal): string
    {
        $signalTime = $signal->created_at;

        $startTime = $signalTime->timestamp * 1000;
        $endTime = Carbon::now()->addHours(4)->timestamp * 1000;

        Log::debug('CheckSignalStatusNew: Fetching historical data', [
            'signal_id' => $signal->id,
            'symbol' => $signal->symbol,
            'signal_time' => $signalTime->toDateTimeString(),
            'start_time_ms' => $startTime,
            'end_time_ms' => $endTime,
        ]);

        $interval = $signal->interval ?? '15m';
        $allKlines = $this->fetchHistoricalKlines($signal->symbol, $interval, $startTime, $endTime);

        if (empty($allKlines)) {
            Log::warning('CheckSignalStatusNew: No klines data received', [
                'signal_id' => $signal->id,
                'symbol' => $signal->symbol,
            ]);

            return 'PROCESSING';
        }

        Log::debug('CheckSignalStatusNew: Klines received', [
            'signal_id' => $signal->id,
            'symbol' => $signal->symbol,
            'klines_count' => count($allKlines),
        ]);

        foreach ($allKlines as $kline) {
            $klineOpenTime = (int) $kline[0];
            $klineCloseTime = (int) $kline[6];
            $high = (float) $kline[2];
            $low = (float) $kline[3];

            if ($klineCloseTime < $startTime) {
                continue;
            }

            if ($signal->type === 'BUY') {
                $hitSL = $low <= (float) $signal->stop_loss;
                $hitTP = $high >= (float) $signal->take_profit;

                if ($hitSL && $hitTP) {
                    Log::info('CheckSignalStatusNew: BUY signal - both SL and TP hit', [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'low' => $low,
                        'high' => $high,
                        'stop_loss' => $signal->stop_loss,
                        'take_profit' => $signal->take_profit,
                    ]);

                    return 'MISSED';
                }
                if ($hitSL) {
                    Log::info('CheckSignalStatusNew: BUY signal - SL hit', [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'low' => $low,
                        'stop_loss' => $signal->stop_loss,
                    ]);

                    return 'MISSED';
                }
                if ($hitTP) {
                    Log::info('CheckSignalStatusNew: BUY signal - TP hit', [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'high' => $high,
                        'take_profit' => $signal->take_profit,
                    ]);

                    return 'DONE';
                }
            } else {
                $hitSL = $high >= (float) $signal->stop_loss;
                $hitTP = $low <= (float) $signal->take_profit;

                if ($hitSL && $hitTP) {
                    Log::info('CheckSignalStatusNew: SELL signal - both SL and TP hit', [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'low' => $low,
                        'high' => $high,
                        'stop_loss' => $signal->stop_loss,
                        'take_profit' => $signal->take_profit,
                    ]);

                    return 'MISSED';
                }
                if ($hitSL) {
                    Log::info('CheckSignalStatusNew: SELL signal - SL hit', [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'kline_time' => Carbon::createFromTimestamp($klineOpenTime / 1000)->toDateTimeString(),
                        'high' => $high,
                        'stop_loss' => $signal->stop_loss,
                    ]);

                    return 'MISSED';
                }
                if ($hitTP) {
                    Log::info('CheckSignalStatusNew: SELL signal - TP hit', [
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

        Log::debug('CheckSignalStatusNew: No TP/SL hit, still processing', [
            'signal_id' => $signal->id,
            'symbol' => $signal->symbol,
            'klines_checked' => count($allKlines),
        ]);

        return 'PROCESSING';
    }

    private function fetchHistoricalKlines(string $symbol, string $interval, int $startTime, int $endTime): array
    {
        try {
            $allKlines = [];
            $currentStartTime = $startTime;
            $limit = 1000;

            $normalizedSymbol = str_ends_with(strtoupper($symbol), 'USDT')
                ? strtoupper($symbol)
                : strtoupper($symbol) . 'USDT';

            $requestCount = 0;
            while ($currentStartTime < $endTime) {
                $requestCount++;
                Log::debug("CheckSignalStatusNew: Fetching klines (request #{$requestCount})", [
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
                    'limit' => $limit,
                ]);

                if (!$response->successful()) {
                    Log::warning('CheckSignalStatusNew: Failed to fetch historical klines', [
                        'symbol' => $normalizedSymbol,
                        'http_status' => $response->status(),
                        'response_body' => $response->body(),
                        'request_number' => $requestCount,
                    ]);
                    break;
                }

                $klines = $response->json();
                if (empty($klines) || !is_array($klines)) {
                    Log::debug('CheckSignalStatusNew: Empty or invalid klines response', [
                        'symbol' => $normalizedSymbol,
                        'request_number' => $requestCount,
                    ]);
                    break;
                }

                Log::debug('CheckSignalStatusNew: Klines received', [
                    'symbol' => $normalizedSymbol,
                    'klines_count' => count($klines),
                    'request_number' => $requestCount,
                ]);

                foreach ($klines as $kline) {
                    $klineTime = (int) $kline[0];
                    if ($klineTime >= $startTime && $klineTime <= $endTime) {
                        $allKlines[] = $kline;
                    }
                }

                if (count($klines) < $limit) {
                    break;
                }

                $lastKlineCloseTime = (int) $klines[count($klines) - 1][6];
                $currentStartTime = $lastKlineCloseTime + 1;

                if ($lastKlineCloseTime >= $endTime) {
                    break;
                }

                usleep(100000);
            }

            usort($allKlines, fn ($a, $b) => $a[0] <=> $b[0]);

            return $allKlines;

        } catch (\Exception $e) {
            Log::error('CheckSignalStatusNew: Exception fetching historical klines', [
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
