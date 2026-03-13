<?php

namespace App\Console\Commands;

use App\Models\CryptoSignal;
use App\Services\CryptoAnalysisService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class EmaRsiMacdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto:ema-rsi-macd
                            {--symbol=* : Символ для анализа (можно указать несколько или через запятую)}
                            {--interval=15m : Таймфрейм (15m, 1h, 4h, etc.)}
                            {--limit=200 : Количество свечей}
                            {--telegram : Отправлять сигналы в Telegram}
                            {--telegram-only : Только отправка в Telegram, без вывода в консоль}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Анализ криптовалют по стратегии EMA+RSI+MACD';

    private CryptoAnalysisService $analysisService;
    private TelegramService $telegramService;
    private array $analysisSignals = [];
    private string $flowId;

    public function __construct(
        CryptoAnalysisService $analysisService,
        TelegramService $telegramService
    ) {
        parent::__construct();
        $this->analysisService = $analysisService;
        $this->telegramService = $telegramService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Логируем запуск команды СРАЗУ для диагностики cron
        Log::info('=== EMA+RSI+MACD Command Handle Called ===', [
            'timestamp' => Carbon::now()->toDateTimeString(),
            'memory_usage' => memory_get_usage(true),
            'is_cron' => !$this->output->isVerbose() && !$this->option('verbose'),
        ]);

        // Генерируем UUID потока для этого запуска команды
        $this->flowId = (string) Str::uuid();

        $startTime = microtime(true);
        $commandStart = Carbon::now();

        $this->info('🚀 Запуск анализа EMA+RSI+MACD стратегии...');
        Log::info('=== EMA+RSI+MACD Command Started ===', [
            'started_at' => $commandStart->toDateTimeString(),
            'flow_id' => $this->flowId,
            'options' => [
                'interval' => $this->option('interval'),
                'limit' => $this->option('limit'),
                'telegram' => $this->option('telegram'),
                'telegram_only' => $this->option('telegram-only'),
            ]
        ]);

        // Получаем список символов
        $symbols = $this->getSymbols();

        if (empty($symbols)) {
            $errorMsg = '❌ Не указаны символы для анализа';
            $this->error($errorMsg);
            Log::error('EMA+RSI+MACD Command Failed: No symbols', [
                'error' => $errorMsg
            ]);
            return Command::FAILURE;
        }

        $this->info("📊 Анализ символов: " . implode(', ', $symbols));
        $this->info("⏱️  Таймфрейм: {$this->option('interval')}");
        $this->info("📈 Лимит свечей: {$this->option('limit')}");
        Log::info('EMA+RSI+MACD Command: Starting analysis', [
            'symbols_count' => count($symbols),
            'symbols' => $symbols,
            'interval' => $this->option('interval'),
            'limit' => $this->option('limit'),
        ]);
        $this->newLine();

        $params = [
            'interval' => $this->option('interval'),
            'limit' => (int) $this->option('limit'),
        ];

        $successCount = 0;
        $errorCount = 0;

        // Анализируем каждый символ
        foreach ($symbols as $symbol) {
            try {
                $this->line("🔍 Анализ {$symbol}...");
                Log::info("EMA+RSI+MACD: Analyzing symbol", ['symbol' => $symbol]);

                $result = $this->analysisService->analyzeEmaRsiMacd($symbol, $params);

                Log::info("EMA+RSI+MACD: Analysis completed", [
                    'symbol' => $symbol,
                    'type' => $result['type'] ?? 'UNKNOWN',
                    'strength' => $result['strength'],
                    'price' => $result['price'],
                    'rsi' => $result['rsi'],
                ]);

                // Сохраняем результат для последующей отправки
                if (!isset($this->analysisSignals[$symbol])) {
                    $this->analysisSignals[$symbol] = [];
                }
                $this->analysisSignals[$symbol][] = $result;

                // Выводим результат в консоль (если не telegram-only)
                if (!$this->option('telegram-only')) {
                    $this->displaySignal($symbol, $result);
                }

                $successCount++;
            } catch (Exception $e) {
                $errorMsg = "❌ Ошибка при анализе {$symbol}: " . $e->getMessage();
                $this->error($errorMsg);
                Log::error("EMA+RSI+MACD: Error analyzing symbol", [
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                $errorCount++;
            }
        }

        $this->newLine();
        $this->info("✅ Успешно: {$successCount}, ❌ Ошибок: {$errorCount}");
        Log::info('EMA+RSI+MACD: Analysis phase completed', [
            'success_count' => $successCount,
            'error_count' => $errorCount,
        ]);

        // Отправка сигналов в Telegram
        if ($this->option('telegram') || $this->option('telegram-only')) {
            $this->sendSignalsToTelegram();
        }

        // Сохранение всех сигналов в базу данных
        $this->saveAllSignalsToDatabase();

        // Фильтрация сигналов текущего потока по дополнительным критериям
        $this->filterFlowSignals();

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        $commandEnd = Carbon::now();

        $this->info("⏱️  Время выполнения: {$executionTime} сек");
        Log::info('=== EMA+RSI+MACD Command Completed ===', [
            'ended_at' => $commandEnd->toDateTimeString(),
            'execution_time_seconds' => $executionTime,
            'total_signals' => count($this->analysisSignals),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'flow_id' => $this->flowId,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Получение списка символов для анализа
     */
    private function getSymbols(): array
    {
        $symbolsOption = $this->option('symbol');

        if (!empty($symbolsOption)) {
            // Если символы указаны через опцию
            $symbols = [];
            foreach ($symbolsOption as $symbol) {
                // Поддержка формата "BTC,ETH,BNB"
                $parts = explode(',', $symbol);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (!empty($part)) {
                        $symbols[] = $part;
                    }
                }
            }
            return array_unique($symbols);
        }

        // Иначе берем из конфигурации
        return config('crypto_symbols.symbols', []);
    }

    /**
     * Отображение сигнала в консоли
     */
    private function displaySignal(string $symbol, array $signal): void
    {
        $type = $signal['type'];
        $strength = $signal['strength'];
        $price = number_format($signal['price'], 2);
        $rsi = number_format($signal['rsi'], 2);
        $longProb = $signal['long_probability'];
        $shortProb = $signal['short_probability'];

        $emoji = $type === 'BUY' ? '🟢' : ($type === 'SELL' ? '🔴' : '⚪');
        $strengthEmoji = match ($strength) {
            'STRONG' => '🔥',
            'MEDIUM' => '⚡',
            'WEAK' => '💡',
            default => '📊',
        };

        $this->line("  {$emoji} {$symbol}: {$type} ({$strengthEmoji} {$strength})");
        $this->line("     Цена: \${$price} | RSI: {$rsi}");
        $this->line("     Вероятности: BUY {$longProb}% | SELL {$shortProb}%");

        if ($signal['stop_loss'] && $signal['take_profit']) {
            $sl = number_format($signal['stop_loss'], 2);
            $tp = number_format($signal['take_profit'], 2);
            $this->line("     SL: \${$sl} | TP: \${$tp}");
        }

        $this->line("     Причина: {$signal['reason']}");
        $this->newLine();
    }

    /**
     * Отправка сигналов в Telegram
     */
    private function sendSignalsToTelegram(): void
    {
        $this->info('📤 Отправка сигналов в Telegram...');
        Log::info('EMA+RSI+MACD: Starting Telegram sending phase');

        $sentCount = 0;
        $skippedCount = 0;
        $skippedByStrength = 0;
        $skippedByMarketContext = 0;
        $skippedByDuplicate = 0;
        $telegramErrors = 0;

        foreach ($this->analysisSignals as $symbol => $signals) {
            foreach ($signals as $signal) {
                // Отправляем только STRONG и MEDIUM сигналы
                if (!in_array($signal['strength'], ['STRONG', 'MEDIUM'])) {
                    $skippedCount++;
                    $skippedByStrength++;
                    Log::debug("EMA+RSI+MACD: Signal skipped (weak strength)", [
                        'symbol' => $symbol,
                        'type' => $signal['type'],
                        'strength' => $signal['strength'],
                    ]);
                    continue;
                }

                // 1. Глобальный фильтр: проверка рыночного контекста (BTC волатильность)
                try {
                    $marketContext = $this->analysisService->checkMarketContext($symbol, $signal['type']);
                    if (!$marketContext['allowed']) {
                        $this->warn("  ⚠️  Пропущен {$symbol} ({$signal['type']}): {$marketContext['reason']}");
                        $skippedCount++;
                        $skippedByMarketContext++;
                        Log::info("EMA+RSI+MACD: Signal skipped (market context)", [
                            'symbol' => $symbol,
                            'type' => $signal['type'],
                            'reason' => $marketContext['reason'],
                        ]);
                        continue;
                    }
                } catch (Exception $e) {
                    Log::error("EMA+RSI+MACD: Error checking market context", [
                        'symbol' => $symbol,
                        'error' => $e->getMessage(),
                    ]);
                }

                // 2. Проверка на дубликаты (не отправляем повторные сигналы)
                try {
                    if (!CryptoSignal::shouldSendSignal(
                        $symbol,
                        $signal['type'],
                        $signal['strength'],
                        'EMA+RSI+MACD',
                        $signal['rsi']
                    )) {
                        $this->warn("  ⚠️  Дубликат сигнала для {$symbol} ({$signal['type']}) - пропущен");
                        $skippedCount++;
                        $skippedByDuplicate++;
                        Log::info("EMA+RSI+MACD: Signal skipped (duplicate)", [
                            'symbol' => $symbol,
                            'type' => $signal['type'],
                            'strength' => $signal['strength'],
                        ]);
                        continue;
                    }
                } catch (Exception $e) {
                    Log::error("EMA+RSI+MACD: Error checking duplicate", [
                        'symbol' => $symbol,
                        'error' => $e->getMessage(),
                    ]);
                }

                // 3. Отправка в Telegram
                try {
                    $sent = $this->telegramService->sendInstantSignal($signal, $symbol, 'EMA+RSI+MACD');

                    if ($sent) {
                        $this->info("  ✅ Отправлен сигнал {$symbol} ({$signal['type']}, {$signal['strength']})");
                        $sentCount++;

                        Log::info("EMA+RSI+MACD: Signal sent to Telegram", [
                            'symbol' => $symbol,
                            'type' => $signal['type'],
                            'strength' => $signal['strength'],
                            'price' => $signal['price'],
                        ]);

                        // Помечаем сигнал как отправленный
                        $signal['sent_to_telegram'] = true;
                    } else {
                        $this->error("  ❌ Ошибка отправки сигнала {$symbol}");
                        $telegramErrors++;
                        Log::error("EMA+RSI+MACD: Failed to send signal to Telegram", [
                            'symbol' => $symbol,
                            'type' => $signal['type'],
                        ]);
                    }
                } catch (Exception $e) {
                    $telegramErrors++;
                    Log::error("EMA+RSI+MACD: Exception sending to Telegram", [
                        'symbol' => $symbol,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }

        $this->newLine();
        $this->info("📊 Отправлено: {$sentCount}, Пропущено: {$skippedCount}");
        Log::info('EMA+RSI+MACD: Telegram sending phase completed', [
            'sent_count' => $sentCount,
            'skipped_total' => $skippedCount,
            'skipped_by_strength' => $skippedByStrength,
            'skipped_by_market_context' => $skippedByMarketContext,
            'skipped_by_duplicate' => $skippedByDuplicate,
            'telegram_errors' => $telegramErrors,
        ]);
    }

    /**
     * Сохранение всех сигналов в базу данных
     *
     * ВАЖНО: Сохраняются ВСЕ сигналы, включая:
     * - HOLD сигналы (когда нет четкого направления)
     * - WEAK сигналы (слабая вероятность)
     * - STRONG и MEDIUM сигналы (сильные сигналы)
     *
     * Это позволяет анализировать историю всех критериев и индикаторов
     */
    private function saveAllSignalsToDatabase(): void
    {
        $this->info('💾 Сохранение сигналов в базу данных...');
        Log::info('EMA+RSI+MACD: Starting database save phase');

        $savedCount = 0;
        $skippedCount = 0;
        $saveErrors = 0;

        foreach ($this->analysisSignals as $symbol => $signals) {
            foreach ($signals as $signal) {
                try {
                    // Сохраняем ВСЕ сигналы со всеми критериями:
                    // - Все индикаторы (EMA, RSI, MACD, ATR)
                    // - Все баллы (long_score, short_score)
                    // - Все вероятности (long_probability, short_probability)
                    // - Все параметры расчета
                    $saved = $this->saveSignalToDatabase($signal, $symbol, $signal['sent_to_telegram'] ?? false);

                    if ($saved) {
                        $savedCount++;
                        Log::debug("EMA+RSI+MACD: Signal saved to database", [
                            'symbol' => $symbol,
                            'type' => $signal['type'],
                            'strength' => $signal['strength'],
                        ]);
                    } else {
                        $skippedCount++;
                    }
                } catch (Exception $e) {
                    $saveErrors++;
                    $errorMsg = "  ❌ Ошибка сохранения сигнала {$symbol}: " . $e->getMessage();
                    $this->error($errorMsg);
                    Log::error("EMA+RSI+MACD: Error saving signal to database", [
                        'symbol' => $symbol,
                        'type' => $signal['type'] ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                }
            }
        }

        $this->info("✅ Сохранено сигналов: {$savedCount}");
        if ($skippedCount > 0) {
            $this->info("⏭️  Пропущено дубликатов (за последние 30 минут): {$skippedCount}");
        }
        if ($saveErrors > 0) {
            $this->warn("⚠️  Ошибок сохранения: {$saveErrors}");
        }
        Log::info('EMA+RSI+MACD: Database save phase completed', [
            'saved_count' => $savedCount,
            'skipped_count' => $skippedCount,
            'save_errors' => $saveErrors,
        ]);
    }

    /**
     * Сохранение одного сигнала в базу данных
     *
     * Сохраняет ВСЕ критерии, использованные для генерации сигнала:
     *
     * ИНДИКАТОРЫ:
     * - EMA(20) и EMA(50) - для определения тренда
     * - RSI(14) - для определения перекупленности/перепроданности
     * - MACD Line, Signal, Histogram - для определения импульса
     * - ATR(14) - для расчета Stop Loss и Take Profit
     *
     * БАЛЛЬНАЯ СИСТЕМА:
     * - long_score - сумма баллов для BUY (максимум ~100)
     * - short_score - сумма баллов для SELL (максимум ~100)
     * - long_probability - вероятность BUY в %
     * - short_probability - вероятность SELL в %
     *
     * РЕЗУЛЬТАТ:
     * - type - BUY/SELL/HOLD (на основе вероятностей)
     * - strength - STRONG/MEDIUM/WEAK (на основе разницы вероятностей)
     * - stop_loss, take_profit - уровни входа/выхода
     */
    private function saveSignalToDatabase(array $signal, string $symbol, bool $sentToTelegram): bool
    {
        $longProb = $signal['long_probability'];
        $shortProb = $signal['short_probability'];

        // Сохраняем сигнал только если одна из вероятностей равна 100
        if ((int)$longProb != 100 && (int)$shortProb != 100) {
            Log::debug("EMA+RSI+MACD: Signal skipped (no probability equals 100)", [
                'symbol' => $symbol,
                'type' => $signal['type'],
                'long_probability' => $longProb,
                'short_probability' => $shortProb,
            ]);
            return false; // Не сохраняем, если ни одна из вероятностей не равна 100
        }

        if ($signal['type'] === 'HOLD') {
            return false;
        }

        // Проверяем, был ли уже сигнал для этого символа с таким же типом за последние 30 минут
        $cutoffTime = Carbon::now()->subMinutes(30);
        $existingSignal = CryptoSignal::where('symbol', $symbol)
            ->where('type', $signal['type'])
            ->where('created_at', '>=', $cutoffTime)
            ->first();

        if ($existingSignal) {
            Log::debug("EMA+RSI+MACD: Signal skipped (duplicate within 30 minutes)", [
                'symbol' => $symbol,
                'type' => $signal['type'],
                'existing_signal_id' => $existingSignal->id,
                'existing_signal_created_at' => $existingSignal->created_at->toDateTimeString(),
            ]);
            return false; // Не сохраняем новый сигнал, если уже есть такой же за последние 30 минут
        }

        $savedSignal = CryptoSignal::saveSignal([
            // Основные данные
            'flow_id' => $this->flowId,
            'symbol' => $symbol,
            'strategy' => 'EMA+RSI+MACD',
            'type' => $signal['type'], // BUY/SELL/HOLD
            'strength' => $signal['strength'], // STRONG/MEDIUM/WEAK

            // Цена
            'price' => $signal['price'],

            // ИНДИКАТОРЫ - все критерии для генерации сигнала
            'rsi' => $signal['rsi'], // RSI(14) - индекс относительной силы
            'ema' => $signal['ema'], // EMA(20) - быстрая скользящая средняя
            'ema_slow' => $signal['ema_slow'], // EMA(50) - медленная скользящая средняя
            'macd' => $signal['macd'], // MACD Line = EMA(12) - EMA(26)
            'macd_signal' => $signal['macd_signal'], // MACD Signal Line = EMA(9) от MACD
            'macd_histogram' => $signal['macd_histogram'], // MACD Histogram = MACD - Signal
            'atr' => $signal['atr'], // ATR(14) - средний истинный диапазон

            // Уровни входа/выхода
            'stop_loss' => $signal['stop_loss'],
            'take_profit' => $signal['take_profit'],

            // БАЛЛЬНАЯ СИСТЕМА - критерии оценки
            'long_score' => $signal['long_score'], // Баллы для BUY (0-100)
            'short_score' => $signal['short_score'], // Баллы для SELL (0-100)
            'long_probability' => $signal['long_probability'], // Вероятность BUY в %
            'short_probability' => $signal['short_probability'], // Вероятность SELL в %

            // Параметры запроса
            'interval' => $this->option('interval'), // Таймфрейм (15m, 1h, etc.)
            'limit' => (int) $this->option('limit'), // Количество свечей (200)

            // Дополнительные данные
            'volume_ratio' => $signal['volume_ratio'],
            'htf_trend' => $signal['htf_trend'],
            'htf_rsi' => $signal['htf_rsi'],
            'ltf_rsi' => $signal['ltf_rsi'],
            'reason' => $signal['reason'], // Текстовое описание причины
            'sent_to_telegram' => $sentToTelegram, // Отправлен ли в Telegram
            'signal_time' => null, // Будет установлено после создания (created_at + 4 часа)
            'status' => null, // Статус будет установлен позже командой проверки
        ]);

        // Обновляем signal_time = created_at + 4 часа
        // Используем refresh() чтобы получить created_at из базы
        $savedSignal->refresh();
        $savedSignal->signal_time = $savedSignal->created_at->copy()->addHours(4);
        $savedSignal->save();

        Log::debug("EMA+RSI+MACD: Signal time set", [
            'signal_id' => $savedSignal->id,
            'symbol' => $symbol,
            'created_at' => $savedSignal->created_at->toDateTimeString(),
            'signal_time' => $savedSignal->signal_time->toDateTimeString(),
        ]);

        return true; // Сигнал успешно сохранен
    }

    /**
     * Фильтрация сигналов текущего потока по заданным критериям
     *
     * Оставляем только сигналы, которые проходят SQL-условия из задачи,
     * остальные сигналы с тем же flow_id удаляем из базы.
     */
    private function filterFlowSignals(): void
    {
        $this->info('🧹 Фильтрация сигналов текущего потока по дополнительным критериям...');
        Log::info('EMA+RSI+MACD: Starting flow filter phase', [
            'flow_id' => $this->flowId,
        ]);

        // Строим запрос, полностью соответствующий заданной SQL
        $matchingSignalsQuery = CryptoSignal::query()
            ->where('flow_id', $this->flowId)
            ->whereNotNull('atr')
            ->whereNotNull('ema')
            ->whereNotNull('macd_histogram')
            ->whereNotNull('take_profit')
            ->where('price', '>', 0)
            ->where('atr', '>', 0)
            // 📏 TP distance filter: (ABS((price - take_profit) / price) * 100) <= 3
            ->whereRaw('(ABS((price - take_profit) / price) * 100) <= 3')
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

        $matchingIds = $matchingSignalsQuery->pluck('id')->all();

        $totalInFlow = CryptoSignal::where('flow_id', $this->flowId)->count();
        $matchedCount = count($matchingIds);

        // Если ни один сигнал не прошел фильтр — удаляем все сигналы этого потока
        if ($matchedCount === 0) {
            CryptoSignal::where('flow_id', $this->flowId)->delete();
            $this->warn("⚠️  Ни один сигнал не прошёл фильтр, удалены все {$totalInFlow} сигналов потока");
            Log::info('EMA+RSI+MACD: Flow filter - no matches, all signals deleted', [
                'flow_id' => $this->flowId,
                'total_in_flow' => $totalInFlow,
            ]);
            return;
        }

        // Удаляем все сигналы этого потока, которые НЕ попали в выборку
        $deletedCount = CryptoSignal::where('flow_id', $this->flowId)
            ->whereNotIn('id', $matchingIds)
            ->delete();

        $this->info("🧾 Фильтр потока: осталось {$matchedCount}, удалено {$deletedCount} из {$totalInFlow}");
        Log::info('EMA+RSI+MACD: Flow filter completed', [
            'flow_id' => $this->flowId,
            'total_in_flow' => $totalInFlow,
            'matched' => $matchedCount,
            'deleted' => $deletedCount,
        ]);
    }
}
