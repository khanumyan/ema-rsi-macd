<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class CryptoAnalysisService
{
    /**
     * In-memory кэш для предотвращения дублирующих запросов
     */
    private array $klinesCache = [];

    /**
     * Получение данных свечей с Binance Futures API
     *
     * @param string $symbol Символ (BTC, BTCUSDT)
     * @param string $interval Таймфрейм (15m, 1h, etc.)
     * @param int $limit Количество свечей
     * @return array Массив свечей
     * @throws Exception
     */
    public function fetchKlines(string $symbol, string $interval = '15m', int $limit = 100): array
    {
        // Нормализация символа (принимает и "BTC", и "BTCUSDT")
        $normalizedSymbol = strtoupper(trim($symbol));
        if (str_ends_with($normalizedSymbol, 'USDT')) {
            $normalizedSymbol = substr($normalizedSymbol, 0, -4);
        }

        // In-memory кэш (чтобы не делать дублирующие запросы)
        $cacheKey = $normalizedSymbol . '|' . $interval . '|' . $limit;
        if (isset($this->klinesCache[$cacheKey])) {
            return $this->klinesCache[$cacheKey];
        }

        // HTTP запрос к Binance Futures API
        $response = Http::timeout(10)->get('https://fapi.binance.com/fapi/v1/klines', [
            'symbol' => $normalizedSymbol . 'USDT',
            'interval' => $interval,
            'limit' => $limit
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to fetch data for {$symbol}: " . $response->status());
        }

        $data = $response->json();

        if (empty($data) || count($data) < 100) {
            throw new Exception("Insufficient data for {$symbol}: got " . count($data) . " candles, need at least 100");
        }

        $this->klinesCache[$cacheKey] = $data; // Сохраняем в кэш
        return $data;
    }

    /**
     * Расчет EMA (Exponential Moving Average)
     *
     * @param array $closes Массив цен закрытия
     * @param int $period Период EMA
     * @return float Значение EMA
     */
    public function calculateEMA(array $closes, int $period): float
    {
        if (count($closes) < $period) {
            return (float) end($closes);
        }

        // Множитель для экспоненциального сглаживания
        $multiplier = 2 / ($period + 1);

        // Начальное значение EMA = SMA первых period свечей
        $ema = array_sum(array_slice($closes, 0, $period)) / $period;

        // Рассчитываем EMA для каждой последующей свечи
        for ($i = $period; $i < count($closes); $i++) {
            $ema = ($closes[$i] * $multiplier) + ($ema * (1 - $multiplier));
        }

        return $ema;
    }

    /**
     * Расчет RSI (Relative Strength Index)
     *
     * @param array $closes Массив цен закрытия
     * @param int $period Период RSI (по умолчанию 14)
     * @return float Значение RSI
     */
    public function calculateRSI(array $closes, int $period = 14): float
    {
        if (count($closes) < $period + 1) {
            return 50.0;
        }

        // Рассчитываем изменения цены (deltas)
        $deltas = [];
        for ($i = 1; $i < count($closes); $i++) {
            $deltas[] = $closes[$i] - $closes[$i - 1];
        }

        // Разделяем на gains (прирост) и losses (убыток)
        $gains = array_map(fn($delta) => max(0, $delta), $deltas);
        $losses = array_map(fn($delta) => max(0, -$delta), $deltas);

        // Начальные средние значения
        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        // Сглаживание по методу Уайлдера (Wilder's smoothing)
        for ($i = $period; $i < count($gains); $i++) {
            $avgGain = (($avgGain * ($period - 1)) + $gains[$i]) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i]) / $period;
        }

        if ($avgLoss == 0) {
            return 100.0; // Если нет убытков, RSI = 100
        }

        $rs = $avgGain / $avgLoss; // Relative Strength
        return 100 - (100 / (1 + $rs)); // RSI
    }

    /**
     * Расчет MACD (Moving Average Convergence Divergence)
     *
     * @param array $closes Массив цен закрытия
     * @param int $fastPeriod Быстрый период (по умолчанию 12)
     * @param int $slowPeriod Медленный период (по умолчанию 26)
     * @param int $signalPeriod Период сигнальной линии (по умолчанию 9)
     * @return array ['macd' => float, 'signal' => float, 'histogram' => float]
     */
    public function calculateMACD(
        array $closes,
        int $fastPeriod = 12,
        int $slowPeriod = 26,
        int $signalPeriod = 9
    ): array {
        if (count($closes) < $slowPeriod + $signalPeriod) {
            return ['macd' => 0, 'signal' => 0, 'histogram' => 0];
        }

        // MACD Line = EMA(12) - EMA(26)
        $emaFast = $this->calculateEMA($closes, $fastPeriod);  // EMA(12)
        $emaSlow = $this->calculateEMA($closes, $slowPeriod);  // EMA(26)
        $macdLine = $emaFast - $emaSlow;

        // Рассчитываем исторические значения MACD для signal line
        $macdValues = [];
        for ($i = $slowPeriod; $i < count($closes); $i++) {
            $slice = array_slice($closes, 0, $i + 1);
            $eFast = $this->calculateEMA($slice, $fastPeriod);
            $eSlow = $this->calculateEMA($slice, $slowPeriod);
            $macdValues[] = $eFast - $eSlow;
        }

        // Signal Line = EMA(9) от MACD Line
        $signalLine = count($macdValues) >= $signalPeriod
            ? $this->calculateEMA($macdValues, $signalPeriod)
            : 0;

        // Histogram = MACD Line - Signal Line
        $histogram = $macdLine - $signalLine;

        return [
            'macd' => $macdLine,
            'signal' => $signalLine,
            'histogram' => $histogram
        ];
    }

    /**
     * Расчет ATR (Average True Range)
     *
     * @param array $highs Массив максимальных цен
     * @param array $lows Массив минимальных цен
     * @param array $closes Массив цен закрытия
     * @param int $period Период ATR (по умолчанию 14)
     * @return float Значение ATR
     */
    public function calculateATR(array $highs, array $lows, array $closes, int $period = 14): float
    {
        if (count($highs) < $period + 1) {
            return 0.0;
        }

        // Рассчитываем True Range для каждой свечи
        $trueRanges = [];
        for ($i = 1; $i < count($highs); $i++) {
            $tr1 = $highs[$i] - $lows[$i];                    // High - Low
            $tr2 = abs($highs[$i] - $closes[$i - 1]);        // |High - Previous Close|
            $tr3 = abs($lows[$i] - $closes[$i - 1]);         // |Low - Previous Close|
            $trueRanges[] = max($tr1, $tr2, $tr3);           // Максимум из трех
        }

        // ATR = среднее значение последних period True Ranges
        return array_sum(array_slice($trueRanges, -$period)) / $period;
    }

    /**
     * Проверка рыночного контекста (волатильность BTC)
     *
     * @param string $symbol Символ для проверки
     * @param string $signalType Тип сигнала (BUY/SELL)
     * @return array ['allowed' => bool, 'reason' => string]
     */
    public function checkMarketContext(string $symbol, string $signalType): array
    {
        try {
            // Получаем данные BTC для проверки волатильности
            $btcKlines = $this->fetchKlines('BTC', '15m', 2);

            if (count($btcKlines) < 2) {
                return ['allowed' => true, 'reason' => 'Insufficient BTC data'];
            }

            $currentPrice = (float) $btcKlines[1][4]; // Текущая цена закрытия
            $previousPrice = (float) $btcKlines[0][4]; // Предыдущая цена закрытия

            // Расчет волатильности в процентах
            $volatility = abs(($currentPrice - $previousPrice) / $previousPrice) * 100;

            // Если BTC волатильность > 3% за 15m → блокируем все сигналы
            if ($volatility > 3.0) {
                return [
                    'allowed' => false,
                    'reason' => "BTC volatility too high: {$volatility}%"
                ];
            }

            // Если BTC падает > 1% за 15m → блокируем SELL сигналы для альтов
            if ($signalType === 'SELL' && $symbol !== 'BTC' && ($currentPrice - $previousPrice) < 0) {
                $btcChange = (($currentPrice - $previousPrice) / $previousPrice) * 100;
                if ($btcChange < -1.0) {
                    return [
                        'allowed' => false,
                        'reason' => "BTC dropping: {$btcChange}%, blocking SELL signals for alts"
                    ];
                }
            }

            return ['allowed' => true, 'reason' => 'Market context OK'];
        } catch (Exception $e) {
            // В случае ошибки разрешаем отправку
            return ['allowed' => true, 'reason' => 'Error checking market context: ' . $e->getMessage()];
        }
    }

    /**
     * Основной метод анализа стратегии EMA+RSI+MACD
     *
     * @param string $symbol Символ для анализа
     * @param array $params Параметры стратегии
     * @return array Результат анализа
     */
    public function analyzeEmaRsiMacd(string $symbol, array $params = []): array
    {
        // Параметры по умолчанию
        $emaFast = $params['ema_fast'] ?? 20;
        $emaSlow = $params['ema_slow'] ?? 50;
        $rsiPeriod = $params['rsi_period'] ?? 14;
        $macdFast = $params['macd_fast'] ?? 12;
        $macdSlow = $params['macd_slow'] ?? 26;
        $macdSignal = $params['macd_signal'] ?? 9;
        $interval = $params['interval'] ?? '15m';
        $limit = $params['limit'] ?? 200;
        $atrPeriod = $params['atr_period'] ?? 14;
        $stopLossMultiplier = $params['stop_loss_multiplier'] ?? 2.3;
        $takeProfitMultiplier = $params['take_profit_multiplier'] ?? 2.0;

        // Получаем данные с биржи
        $klines = $this->fetchKlines($symbol, $interval, $limit);

        // Извлекаем массивы цен
        $closes = array_map(fn($k) => (float) $k[4], $klines); // [4] = close price
        $highs = array_map(fn($k) => (float) $k[2], $klines);  // [2] = high price
        $lows = array_map(fn($k) => (float) $k[3], $klines);   // [3] = low price

        $price = end($closes); // Текущая цена (последняя закрытая свеча)

        // Расчет индикаторов
        $ema20 = $this->calculateEMA($closes, $emaFast);
        $ema50 = $this->calculateEMA($closes, $emaSlow);
        $rsi = $this->calculateRSI($closes, $rsiPeriod);
        $macd = $this->calculateMACD($closes, $macdFast, $macdSlow, $macdSignal);
        $atr = $this->calculateATR($highs, $lows, $closes, $atrPeriod);

        $macdLine = $macd['macd'];
        $macdSignalLine = $macd['signal'];
        $macdHist = $macd['histogram'];

        // Защита от деления на ноль
        if ($atr <= 0) {
            $atr = $price * 0.01; // Fallback: 1% от цены
        }

        // ===== НОРМАЛИЗАЦИЯ ЧЕРЕЗ ATR =====
        // MACD histogram в долях ATR
        $macdHistAtr = $macdHist / $atr;
        
        // EMA distance в долях ATR
        $emaDistance = abs($price - $ema20);
        $emaDistanceAtr = $emaDistance / $atr;
        
        // ATR в процентах от цены
        $atrPct = ($atr / $price) * 100;

        // Инициализация баллов (для обратной совместимости и score difference)
        $longScore = 0;
        $shortScore = 0;

        // ===== БАЛЛЬНАЯ СИСТЕМА (для расчета score difference) =====
        // BUY баллы
        if ($price > $ema20 && $ema20 > $ema50) {
            $longScore += 30;
        }
        if ($macdLine > 0 && $macdHist > 0) {
            $longScore += 30;
        }
        if ($rsi >= 48 && $rsi <= 60) {
            $longScore += 20;
        }
        if ($macdHist > 0 && $macdLine > $macdSignalLine) {
            $longScore += 20;
        }

        // SELL баллы
        if ($price < $ema20 && $ema20 < $ema50) {
            $shortScore += 30;
        }
        if ($macdLine < 0 && $macdHist < 0) {
            $shortScore += 30;
        }
        if ($rsi >= 40 && $rsi <= 52) {
            $shortScore += 20;
        }
        if ($macdHist < 0 && $macdLine < $macdSignalLine) {
            $shortScore += 20;
        }

        // Score difference
        $scoreDiff = $longScore - $shortScore;

        // ===== BUY CONDITIONS (через ATR) =====
        $buyConditions = [
            'rsi' => ($rsi >= 48 && $rsi <= 60),
            'macd_hist_atr' => ($macdHistAtr >= 0.25),
            'ema_distance_atr' => ($emaDistanceAtr >= 0.5 && $emaDistanceAtr <= 1.5),
            'atr_pct' => ($atrPct >= 0.3 && $atrPct <= 3.0),
            'score_diff' => ($scoreDiff >= 10 && $scoreDiff <= 20),
        ];
        $buyAllConditionsMet = array_reduce($buyConditions, fn($carry, $condition) => $carry && $condition, true);

        // ===== SELL CONDITIONS (через ATR) =====
        $sellConditions = [
            'rsi' => ($rsi >= 40 && $rsi <= 52),
            'macd_hist_atr' => (abs($macdHist) / $atr >= 0.25),
            'ema_distance_atr' => ($emaDistanceAtr >= 0.5 && $emaDistanceAtr <= 1.5),
            'atr_pct' => ($atrPct >= 0.3 && $atrPct <= 3.0),
            'score_diff' => (($shortScore - $longScore) >= 10 && ($shortScore - $longScore) <= 20),
        ];
        $sellAllConditionsMet = array_reduce($sellConditions, fn($carry, $condition) => $carry && $condition, true);

        // Нормализация баллов в вероятности (для обратной совместимости)
        $totalScore = $longScore + $shortScore;
        $longProb = $totalScore > 0
            ? round(($longScore / $totalScore) * 100)
            : 50;
        $shortProb = $totalScore > 0
            ? round(($shortScore / $totalScore) * 100)
            : 50;

        // Определение сигнала на основе новых критериев
        $signal = 'HOLD';
        if ($buyAllConditionsMet) {
            $signal = 'BUY';
        } elseif ($sellAllConditionsMet) {
            $signal = 'SELL';
        }

        // Определение силы сигнала
        $probDifference = abs($longProb - $shortProb);
        $strength = 'WEAK';
        if ($probDifference > 20) {
            $strength = 'STRONG';
        } elseif ($probDifference > 10) {
            $strength = 'MEDIUM';
        }

        // Расчет Stop Loss и Take Profit
        $stopLoss = null;
        $takeProfit = null;

        if ($signal === 'BUY') {
            $stopLoss = $price - ($atr * $stopLossMultiplier);
            $takeProfit = $price + ($atr * $takeProfitMultiplier);
        } elseif ($signal === 'SELL') {
            $stopLoss = $price + ($atr * $stopLossMultiplier);
            $takeProfit = $price - ($atr * $takeProfitMultiplier);
        }

        // Формирование причины сигнала с новыми критериями
        $reason = $this->buildReason(
            $price, 
            $ema20, 
            $ema50, 
            $rsi, 
            $macdLine, 
            $macdHist, 
            $signal,
            $macdHistAtr,
            $emaDistanceAtr,
            $atrPct,
            $scoreDiff
        );

        return [
            'signal' => $signal,  // Для совместимости
            'type' => $signal,    // Основной ключ (BUY/SELL/HOLD)
            'strength' => $strength,
            'price' => $price,
            'rsi' => $rsi,
            'ema' => $ema20,
            'ema_slow' => $ema50,
            'macd' => $macdLine,
            'macd_signal' => $macdSignalLine,
            'macd_histogram' => $macdHist,
            'atr' => $atr,
            'macd_hist_atr' => $macdHistAtr,  // Нормализованный MACD histogram
            'ema_distance_atr' => $emaDistanceAtr,  // Нормализованное расстояние EMA
            'atr_pct' => $atrPct,  // ATR в процентах
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'volume_ratio' => 1.0,
            'htf_trend' => 'N/A',
            'htf_rsi' => 0,
            'ltf_rsi' => 0,
            'long_score' => $longScore,
            'short_score' => $shortScore,
            'long_probability' => $longProb,
            'short_probability' => $shortProb,
            'score_diff' => $scoreDiff,
            'reason' => $reason,
        ];
    }

    /**
     * Формирование текста причины сигнала с ATR нормализацией
     */
    private function buildReason(
        float $price,
        float $ema20,
        float $ema50,
        float $rsi,
        float $macdLine,
        float $macdHist,
        string $signal,
        float $macdHistAtr,
        float $emaDistanceAtr,
        float $atrPct,
        int $scoreDiff
    ): string {
        $trend = $ema20 > $ema50 ? 'Bullish' : 'Bearish';
        $priceVsEma = $price > $ema20 ? 'above' : 'below';
        $macdStatus = $macdLine > 0 ? 'above zero' : 'below zero';

        return sprintf(
            'RSI: %.2f | MACD Hist/ATR: %.3f | EMA Dist/ATR: %.3f | ATR%%: %.2f | Score Diff: %d | Trend: %s | Price %s EMA20',
            $rsi,
            $macdHistAtr,
            $emaDistanceAtr,
            $atrPct,
            $scoreDiff,
            $trend,
            $priceVsEma
        );
    }
}

