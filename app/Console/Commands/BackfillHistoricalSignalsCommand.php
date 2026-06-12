<?php

namespace App\Console\Commands;

use App\Services\CryptoAnalysisService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BackfillHistoricalSignalsCommand extends Command
{
    private const TARGET_TABLE   = 'crypto_sygnals_new';
    private const TP_MULTIPLIER  = 3.5;  // было 2.5 — увеличен для лучшего R:R
    private const SL_MULTIPLIER  = 2.3;  // было 3.3 — уменьшен для меньших потерь

    protected $signature = 'signals:backfill-historical
                            {--symbol=* : Символы для анализа (можно несколько или через запятую)}
                            {--from=2026-01-01 00:00:00 : Дата начала анализа}
                            {--to=now : Дата конца анализа}
                            {--interval=15m : Таймфрейм свечей}
                            {--limit=200 : Количество свечей в окне анализа}
                            {--truncate : Очистить целевую таблицу перед запуском}';

    protected $description = 'Исторический поиск сигналов EMA+RSI+MACD (оптимальные критерии + TP=3.5/SL=2.0) с немедленным расчётом DONE/MISSED';

    public function __construct(
        private readonly CryptoAnalysisService $analysisService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!Schema::hasTable(self::TARGET_TABLE)) {
            $this->error('Таблица crypto_sygnals_new не найдена. Сначала выполните миграции: php artisan migrate');
            return Command::FAILURE;
        }

        $from = Carbon::parse($this->option('from'))->utc();
        $toOption = (string) $this->option('to');
        $to = strtolower($toOption) === 'now'
            ? Carbon::now('UTC')
            : Carbon::parse($toOption)->utc();

        if ($from->gte($to)) {
            $this->error('Параметр --from должен быть раньше --to');
            return Command::FAILURE;
        }

        $interval = (string) $this->option('interval');
        $limit    = max(100, (int) $this->option('limit'));
        $symbols  = $this->getSymbols();

        if (empty($symbols)) {
            $this->error('Не найдены символы для анализа');
            return Command::FAILURE;
        }

        if ($this->option('truncate')) {
            DB::table(self::TARGET_TABLE)->truncate();
            $this->warn('Таблица crypto_sygnals_new очищена перед запуском');
        }

        $runId       = (string) Str::uuid();
        $wallStart   = microtime(true);
        $intervalMs  = $this->intervalToMilliseconds($interval);
        $historyStart = $from->copy()->subMilliseconds($intervalMs * $limit);

        $this->info('=== Исторический бэктест ===');
        $this->info(sprintf('TP: %.1f× ATR | SL: %.1f× ATR | R:R: %.2f', self::TP_MULTIPLIER, self::SL_MULTIPLIER, self::TP_MULTIPLIER / self::SL_MULTIPLIER));
        $this->line("Период:    {$from->toDateTimeString()} -> {$to->toDateTimeString()}");
        $this->line("Таймфрейм: {$interval}, окно: {$limit} свечей");
        $this->line('Символов:  ' . count($symbols));
        $this->line('Критерии:  5 оптимальных сценариев (BUY#1, BUY#2, SELL#1, SELL#2, SELL#3)');

        $targetColumns = array_flip(Schema::getColumnListing(self::TARGET_TABLE));

        $saved               = 0;
        $processed           = 0;
        $errors              = 0;
        $skippedByStrength   = 0;
        $skippedByCriteria   = 0;
        $skippedByDuplicate  = 0;
        $doneCount           = 0;
        $missedCount         = 0;
        $processingCount     = 0;
        $scenarioCounts      = ['BUY#1' => 0, 'BUY#2' => 0, 'SELL#1' => 0, 'SELL#2' => 0, 'SELL#3' => 0];

        $fromMs = $this->toMilliseconds($from);
        $toMs   = $this->toMilliseconds($to);
        $batch  = [];

        foreach ($symbols as $symbol) {
            try {
                $this->line("Анализ {$symbol}...");

                $klines = $this->fetchHistoricalKlines(
                    $symbol,
                    $interval,
                    $this->toMilliseconds($historyStart),
                    $toMs
                );

                if (count($klines) < $limit) {
                    $this->warn("  Недостаточно свечей для {$symbol}, пропуск");
                    continue;
                }

                foreach ($klines as $index => $kline) {
                    $closeTimeMs = (int) $kline[6];

                    if ($closeTimeMs < $fromMs) {
                        continue;
                    }

                    if ($closeTimeMs > $toMs) {
                        break;
                    }

                    $windowStart = $index - $limit + 1;
                    if ($windowStart < 0) {
                        continue;
                    }

                    try {
                        $signal = $this->analysisService->analyzeEmaRsiMacdFromKlines(
                            array_slice($klines, $windowStart, $limit),
                            [
                                'interval'              => $interval,
                                'limit'                 => $limit,
                                'stop_loss_multiplier'  => self::SL_MULTIPLIER,
                                'take_profit_multiplier' => self::TP_MULTIPLIER,
                            ]
                        );
                    } catch (Exception) {
                        continue;
                    }

                    $processed++;

                    if (!in_array($signal['type'], ['BUY', 'SELL'], true)) {
                        continue;
                    }

                    if (($signal['strength'] ?? '') !== 'STRONG') {
                        $skippedByStrength++;
                        continue;
                    }

                    if ($signal['stop_loss'] === null || $signal['take_profit'] === null) {
                        continue;
                    }

                    $scenario = $this->matchesOptimalScenario($signal);
                    if ($scenario === null) {
                        $skippedByCriteria++;
                        continue;
                    }

                    $createdAt = Carbon::createFromTimestamp((int) floor($closeTimeMs / 1000), 'UTC');

                    if ($this->hasRecentDuplicate($symbol, $signal['type'], $createdAt, $runId, $batch)) {
                        $skippedByDuplicate++;
                        continue;
                    }

                    // Определяем DONE / MISSED / PROCESSING по последующим свечам
                    $status = $this->checkSignalStatusFromKlines(
                        $signal,
                        array_slice($klines, $index),
                        $closeTimeMs
                    );

                    match ($status) {
                        'DONE'       => $doneCount++,
                        'MISSED'     => $missedCount++,
                        default      => $processingCount++,
                    };

                    $scenarioCounts[$scenario]++;

                    $payload = [
                        'flow_id'          => $runId,
                        'symbol'           => strtoupper($symbol),
                        'strategy'         => 'EMA+RSI+MACD',
                        'type'             => $signal['type'],
                        'strength'         => $signal['strength'],
                        'price'            => $signal['price'],
                        'rsi'              => $signal['rsi'],
                        'ema'              => $signal['ema'],
                        'ema_slow'         => $signal['ema_slow'],
                        'macd'             => $signal['macd'],
                        'macd_signal'      => $signal['macd_signal'],
                        'macd_histogram'   => $signal['macd_histogram'],
                        'atr'              => $signal['atr'],
                        'stop_loss'        => $signal['stop_loss'],
                        'take_profit'      => $signal['take_profit'],
                        'volume_ratio'     => $signal['volume_ratio'] ?? 1.0,
                        'htf_trend'        => $signal['htf_trend'] ?? 'N/A',
                        'htf_rsi'          => $signal['htf_rsi'] ?? 0,
                        'ltf_rsi'          => $signal['ltf_rsi'] ?? 0,
                        'long_score'       => $signal['long_score'],
                        'short_score'      => $signal['short_score'],
                        'long_probability' => $signal['long_probability'],
                        'short_probability' => $signal['short_probability'],
                        'interval'         => $interval,
                        'limit'            => $limit,
                        'reason'           => $signal['reason'] ?? null,
                        'sent_to_telegram' => false,
                        'signal_time'      => $createdAt->copy()->addHours(4),
                        'status'           => $status,
                        'created_at'       => $createdAt,
                        'updated_at'       => $createdAt,
                    ];

                    $batch[] = array_intersect_key($payload, $targetColumns);

                    if (count($batch) >= 250) {
                        DB::table(self::TARGET_TABLE)->insert($batch);
                        $saved += count($batch);
                        $batch  = [];
                    }
                }
            } catch (Exception $e) {
                $errors++;
                $this->error("  Ошибка {$symbol}: {$e->getMessage()}");
                Log::error('BackfillHistorical: symbol failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            }
        }

        if (!empty($batch)) {
            DB::table(self::TARGET_TABLE)->insert($batch);
            $saved += count($batch);
        }

        $duration = round(microtime(true) - $wallStart, 2);
        $donePct  = ($doneCount + $missedCount) > 0
            ? round($doneCount / ($doneCount + $missedCount) * 100, 2)
            : 0;

        $this->newLine();
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Run ID',               $runId],
                ['Сигналов сохранено',   $saved],
                ['Окон обработано',      $processed],
                ['Пропуск: не STRONG',   $skippedByStrength],
                ['Пропуск: критерии',    $skippedByCriteria],
                ['Пропуск: дубликаты',   $skippedByDuplicate],
                ['DONE',                 $doneCount],
                ['MISSED',               $missedCount],
                ['PROCESSING',           $processingCount],
                ['Done %',               $donePct . '%'],
                ['TP multiplier',        self::TP_MULTIPLIER . '×'],
                ['SL multiplier',        self::SL_MULTIPLIER . '×'],
                ['Ошибок символов',      $errors],
                ['Время (сек)',           $duration],
            ]
        );

        $this->info('Распределение по сценариям:');
        foreach ($scenarioCounts as $sc => $cnt) {
            $this->line("  {$sc}: {$cnt}");
        }

        Log::info('BackfillHistorical: completed', [
            'run_id'          => $runId,
            'saved'           => $saved,
            'done'            => $doneCount,
            'missed'          => $missedCount,
            'processing'      => $processingCount,
            'done_pct'        => $donePct,
            'scenario_counts' => $scenarioCounts,
            'tp_multiplier'   => self::TP_MULTIPLIER,
            'sl_multiplier'   => self::SL_MULTIPLIER,
            'duration_seconds' => $duration,
        ]);

        return Command::SUCCESS;
    }

    /**
     * 5 оптимальных сценариев на основе анализа 1.4М реальных сделок.
     * Возвращает название сценария или null если сигнал не подходит.
     *
     * BUY#1  62.5% done | BUY#2  58.1% done
     * SELL#1 69.5% done | SELL#2 67.6% done | SELL#3 63.8% done
     */
    private function matchesOptimalScenario(array $signal): ?string
    {
        $price   = (float) ($signal['price'] ?? 0);
        $atr     = (float) ($signal['atr'] ?? 0);
        $rsi     = (float) ($signal['rsi'] ?? 0);
        $macd    = (float) ($signal['macd'] ?? 0);
        $hist    = (float) ($signal['macd_histogram'] ?? 0);
        $ema     = (float) ($signal['ema'] ?? 0);
        $emaSlow = (float) ($signal['ema_slow'] ?? 0);
        $type    = $signal['type'] ?? '';

        if ($price <= 0 || $atr <= 0) {
            return null;
        }

        $atrPct      = ($atr / $price) * 100;
        $histAbsPct  = (abs($hist) / $price) * 10000;  // в базисных пунктах ×100

        // --- BUY #1: Разворотный (CROSS_UP + RSI 42-58 + большая гистограмма + ATR>1.5% + даунтренд) ---
        if ($type === 'BUY'
            && $macd < 0 && $hist > 0
            && $rsi >= 42.0 && $rsi <= 58.0
            && ($hist / $price) * 10000 >= 15.0
            && $atrPct > 1.5
            && $ema < $emaSlow
        ) {
            return 'BUY#1';
        }

        // --- BUY #2: Импульсный (BULL + RSI 50-58 + большая гистограмма + ATR 0.6-1.5%) ---
        if ($type === 'BUY'
            && $macd > 0 && $hist > 0
            && $rsi >= 50.0 && $rsi <= 58.0
            && ($hist / $price) * 10000 >= 15.0
            && $atrPct >= 0.6 && $atrPct <= 1.5
        ) {
            return 'BUY#2';
        }

        // --- SELL #1: Перепродан (BEAR + RSI<35 + ATR>1.5% + средняя гистограмма + аптренд) ---
        if ($type === 'SELL'
            && $macd < 0 && $hist < 0
            && $rsi < 35.0
            && $atrPct > 1.5
            && $histAbsPct >= 5.0 && $histAbsPct <= 40.0
            && $ema > $emaSlow
        ) {
            return 'SELL#1';
        }

        // --- SELL #2: Слабый RSI (BEAR + RSI 35-42 + ATR>1.5% + средняя гистограмма + аптренд) ---
        if ($type === 'SELL'
            && $macd < 0 && $hist < 0
            && $rsi >= 35.0 && $rsi <= 42.0
            && $atrPct > 1.5
            && $histAbsPct >= 5.0 && $histAbsPct <= 40.0
            && $ema > $emaSlow
        ) {
            return 'SELL#2';
        }

        // --- SELL #3: Дивергентный (BEAR + RSI 42-50 + ATR<0.6% + средняя гистограмма + аптренд) ---
        if ($type === 'SELL'
            && $macd < 0 && $hist < 0
            && $rsi >= 42.0 && $rsi <= 50.0
            && $ema > $emaSlow
            && $atrPct < 0.6
            && $histAbsPct >= 5.0 && $histAbsPct <= 40.0
        ) {
            return 'SELL#3';
        }

        return null;
    }

    /**
     * Определяет статус сигнала по последующим свечам (та же логика что в CheckSignalStatusNewCommand).
     */
    private function checkSignalStatusFromKlines(array $signal, array $klines, int $startTimeMs): string
    {
        $tp   = (float) $signal['take_profit'];
        $sl   = (float) $signal['stop_loss'];
        $type = $signal['type'];

        foreach ($klines as $kline) {
            $closeTime = (int) $kline[6];

            if ($closeTime < $startTimeMs) {
                continue;
            }

            $high = (float) $kline[2];
            $low  = (float) $kline[3];

            if ($type === 'BUY') {
                $hitSL = $low  <= $sl;
                $hitTP = $high >= $tp;
            } else {
                $hitSL = $high >= $sl;
                $hitTP = $low  <= $tp;
            }

            if ($hitSL) {
                return 'MISSED';
            }

            if ($hitTP) {
                return 'DONE';
            }
        }

        return 'PROCESSING';
    }

    private function hasRecentDuplicate(string $symbol, string $type, Carbon $createdAt, string $runId, array $currentBatch): bool
    {
        $cutoff = $createdAt->copy()->subMinutes(30);

        foreach ($currentBatch as $row) {
            if (($row['symbol'] ?? null) !== strtoupper($symbol) || ($row['type'] ?? null) !== $type) {
                continue;
            }

            $rowTime = $row['created_at'] ?? null;
            if ($rowTime instanceof Carbon && $rowTime->gte($cutoff)) {
                return true;
            }
        }

        return DB::table(self::TARGET_TABLE)
            ->where('flow_id', $runId)
            ->where('symbol', strtoupper($symbol))
            ->where('type', $type)
            ->where('created_at', '>=', $cutoff)
            ->where('created_at', '<=', $createdAt)
            ->exists();
    }

    private function getSymbols(): array
    {
        $symbolsOption = $this->option('symbol');

        if (!empty($symbolsOption)) {
            $symbols = [];
            foreach ($symbolsOption as $symbol) {
                foreach (explode(',', (string) $symbol) as $part) {
                    $part = strtoupper(trim($part));
                    if ($part !== '') {
                        $symbols[] = $part;
                    }
                }
            }

            return array_values(array_unique($symbols));
        }

        return array_values(array_unique(array_map(
            static fn(string $symbol) => strtoupper(trim($symbol)),
            config('crypto_symbols.symbols', [])
        )));
    }

    private function fetchHistoricalKlines(string $symbol, string $interval, int $startTime, int $endTime): array
    {
        $allKlines = [];
        $current   = $startTime;
        $limit     = 1000;

        $normalizedSymbol = str_ends_with(strtoupper($symbol), 'USDT')
            ? strtoupper($symbol)
            : strtoupper($symbol) . 'USDT';

        while ($current < $endTime) {
            $response = Http::timeout(30)->get('https://fapi.binance.com/fapi/v1/klines', [
                'symbol'    => $normalizedSymbol,
                'interval'  => $interval,
                'startTime' => $current,
                'endTime'   => $endTime,
                'limit'     => $limit,
            ]);

            if (!$response->successful()) {
                throw new Exception("Failed klines request for {$normalizedSymbol}, status {$response->status()}");
            }

            $klines = $response->json();
            if (empty($klines) || !is_array($klines)) {
                break;
            }

            foreach ($klines as $kline) {
                $openTime = (int) $kline[0];
                if ($openTime >= $startTime && $openTime <= $endTime) {
                    $allKlines[] = $kline;
                }
            }

            if (count($klines) < $limit) {
                break;
            }

            $lastClose = (int) $klines[count($klines) - 1][6];
            if ($lastClose >= $endTime) {
                break;
            }

            $current = $lastClose + 1;
            usleep(120_000);
        }

        usort($allKlines, static fn($a, $b) => $a[0] <=> $b[0]);

        return $allKlines;
    }

    private function intervalToMilliseconds(string $interval): int
    {
        if (!preg_match('/^(\d+)([mhdw])$/i', $interval, $m)) {
            throw new Exception("Unsupported interval format: {$interval}");
        }

        return (int) $m[1] * match (strtolower($m[2])) {
            'm'     => 60 * 1_000,
            'h'     => 3_600 * 1_000,
            'd'     => 86_400 * 1_000,
            'w'     => 604_800 * 1_000,
            default => throw new Exception("Unsupported interval unit: {$m[2]}"),
        };
    }

    private function toMilliseconds(Carbon $dt): int
    {
        return $dt->getTimestamp() * 1_000;
    }
}
