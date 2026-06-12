<?php

namespace Database\Seeders;

use App\Models\Indicator;
use Illuminate\Database\Seeder;

class IndicatorSeeder extends Seeder
{
    public function run(): void
    {
        $indicators = [
            // ── MOMENTUM ──────────────────────────────────────────────────
            [
                'name'        => 'RSI (Relative Strength Index)',
                'short_name'  => 'RSI',
                'category'    => 'momentum',
                'description' => 'Индекс относительной силы. Измеряет скорость и изменение ценовых движений. Диапазон 0–100.',
                'params'      => [['name'=>'period','type'=>'int','default'=>14,'label'=>'Период','min'=>2,'max'=>100]],
                'outputs'     => ['rsi'],
            ],
            [
                'name'        => 'Stochastic Oscillator',
                'short_name'  => 'STOCH',
                'category'    => 'momentum',
                'description' => 'Стохастический осциллятор (%K и %D). Сравнивает текущую цену с диапазоном за период.',
                'params'      => [
                    ['name'=>'k_period','type'=>'int','default'=>14,'label'=>'%K период'],
                    ['name'=>'d_period','type'=>'int','default'=>3,'label'=>'%D период'],
                    ['name'=>'smooth', 'type'=>'int','default'=>3,'label'=>'Сглаживание'],
                ],
                'outputs'     => ['k', 'd'],
            ],
            [
                'name'        => 'CCI (Commodity Channel Index)',
                'short_name'  => 'CCI',
                'category'    => 'momentum',
                'description' => 'Индекс товарного канала. Измеряет отклонение цены от её статистического среднего.',
                'params'      => [['name'=>'period','type'=>'int','default'=>20,'label'=>'Период']],
                'outputs'     => ['cci'],
            ],
            [
                'name'        => 'Williams %R',
                'short_name'  => 'WILLR',
                'category'    => 'momentum',
                'description' => 'Осциллятор Уильямса. Диапазон от -100 до 0. -80 и ниже — перепроданность.',
                'params'      => [['name'=>'period','type'=>'int','default'=>14,'label'=>'Период']],
                'outputs'     => ['willr'],
            ],
            [
                'name'        => 'MFI (Money Flow Index)',
                'short_name'  => 'MFI',
                'category'    => 'momentum',
                'description' => 'Индекс денежного потока. RSI с учётом объёма.',
                'params'      => [['name'=>'period','type'=>'int','default'=>14,'label'=>'Период']],
                'outputs'     => ['mfi'],
            ],
            [
                'name'        => 'ROC (Rate of Change)',
                'short_name'  => 'ROC',
                'category'    => 'momentum',
                'description' => 'Скорость изменения цены в процентах за N периодов.',
                'params'      => [['name'=>'period','type'=>'int','default'=>12,'label'=>'Период']],
                'outputs'     => ['roc'],
            ],

            // ── TREND ─────────────────────────────────────────────────────
            [
                'name'        => 'EMA (Exponential Moving Average)',
                'short_name'  => 'EMA',
                'category'    => 'trend',
                'description' => 'Экспоненциальная скользящая средняя. Придаёт больший вес последним данным.',
                'params'      => [
                    ['name'=>'period','type'=>'int','default'=>20,'label'=>'Период','min'=>2,'max'=>500],
                    ['name'=>'source','type'=>'select','default'=>'close','label'=>'Источник','options'=>['open','high','low','close']],
                ],
                'outputs'     => ['ema'],
            ],
            [
                'name'        => 'SMA (Simple Moving Average)',
                'short_name'  => 'SMA',
                'category'    => 'trend',
                'description' => 'Простая скользящая средняя. Среднее значение цены за N периодов.',
                'params'      => [
                    ['name'=>'period','type'=>'int','default'=>20,'label'=>'Период','min'=>2,'max'=>500],
                    ['name'=>'source','type'=>'select','default'=>'close','label'=>'Источник','options'=>['open','high','low','close']],
                ],
                'outputs'     => ['sma'],
            ],
            [
                'name'        => 'WMA (Weighted Moving Average)',
                'short_name'  => 'WMA',
                'category'    => 'trend',
                'description' => 'Взвешенная скользящая средняя. Более свежие свечи имеют больший вес.',
                'params'      => [['name'=>'period','type'=>'int','default'=>20,'label'=>'Период']],
                'outputs'     => ['wma'],
            ],
            [
                'name'        => 'MACD (Moving Average Convergence Divergence)',
                'short_name'  => 'MACD',
                'category'    => 'trend',
                'description' => 'MACD линия, сигнальная линия и гистограмма. Показывает направление и силу тренда.',
                'params'      => [
                    ['name'=>'fast_period',  'type'=>'int','default'=>12,'label'=>'Быстрый EMA'],
                    ['name'=>'slow_period',  'type'=>'int','default'=>26,'label'=>'Медленный EMA'],
                    ['name'=>'signal_period','type'=>'int','default'=>9, 'label'=>'Сигнальный EMA'],
                ],
                'outputs'     => ['macd', 'signal', 'histogram'],
            ],
            [
                'name'        => 'ADX (Average Directional Index)',
                'short_name'  => 'ADX',
                'category'    => 'trend',
                'description' => 'Средний индекс направленного движения. Измеряет силу тренда (не направление).',
                'params'      => [['name'=>'period','type'=>'int','default'=>14,'label'=>'Период']],
                'outputs'     => ['adx', 'di_plus', 'di_minus'],
            ],
            [
                'name'        => 'Parabolic SAR',
                'short_name'  => 'SAR',
                'category'    => 'trend',
                'description' => 'Параболический SAR. Точки выше цены — нисходящий тренд, ниже — восходящий.',
                'params'      => [
                    ['name'=>'step','type'=>'float','default'=>0.02,'label'=>'Шаг'],
                    ['name'=>'max_step','type'=>'float','default'=>0.2,'label'=>'Макс. шаг'],
                ],
                'outputs'     => ['sar'],
            ],
            [
                'name'        => 'Ichimoku Cloud',
                'short_name'  => 'ICHIMOKU',
                'category'    => 'trend',
                'description' => 'Облако Ишимоку: Tenkan-sen, Kijun-sen, Senkou Span A/B, Chikou Span.',
                'params'      => [
                    ['name'=>'tenkan','type'=>'int','default'=>9, 'label'=>'Tenkan-sen'],
                    ['name'=>'kijun', 'type'=>'int','default'=>26,'label'=>'Kijun-sen'],
                    ['name'=>'senkou','type'=>'int','default'=>52,'label'=>'Senkou Span B'],
                ],
                'outputs'     => ['tenkan', 'kijun', 'senkou_a', 'senkou_b', 'chikou'],
            ],
            [
                'name'        => 'Supertrend',
                'short_name'  => 'SUPERTREND',
                'category'    => 'trend',
                'description' => 'Суперттренд на основе ATR. Зелёный — восходящий тренд, красный — нисходящий.',
                'params'      => [
                    ['name'=>'period',     'type'=>'int',  'default'=>10, 'label'=>'ATR период'],
                    ['name'=>'multiplier', 'type'=>'float','default'=>3.0,'label'=>'Множитель ATR'],
                ],
                'outputs'     => ['supertrend', 'direction'],
            ],

            // ── VOLATILITY ────────────────────────────────────────────────
            [
                'name'        => 'ATR (Average True Range)',
                'short_name'  => 'ATR',
                'category'    => 'volatility',
                'description' => 'Средний истинный диапазон. Измеряет волатильность рынка.',
                'params'      => [['name'=>'period','type'=>'int','default'=>14,'label'=>'Период']],
                'outputs'     => ['atr'],
            ],
            [
                'name'        => 'Bollinger Bands',
                'short_name'  => 'BB',
                'category'    => 'volatility',
                'description' => 'Полосы Боллинджера: средняя линия (SMA) и верхняя/нижняя полосы (SMA ± K×σ).',
                'params'      => [
                    ['name'=>'period',     'type'=>'int',  'default'=>20, 'label'=>'Период SMA'],
                    ['name'=>'multiplier', 'type'=>'float','default'=>2.0,'label'=>'Множитель σ'],
                ],
                'outputs'     => ['upper', 'middle', 'lower'],
            ],
            [
                'name'        => 'Keltner Channel',
                'short_name'  => 'KC',
                'category'    => 'volatility',
                'description' => 'Канал Кельтнера на основе EMA и ATR.',
                'params'      => [
                    ['name'=>'ema_period', 'type'=>'int',  'default'=>20, 'label'=>'EMA период'],
                    ['name'=>'atr_period', 'type'=>'int',  'default'=>10, 'label'=>'ATR период'],
                    ['name'=>'multiplier', 'type'=>'float','default'=>2.0,'label'=>'Множитель ATR'],
                ],
                'outputs'     => ['upper', 'middle', 'lower'],
            ],
            [
                'name'        => 'Donchian Channel',
                'short_name'  => 'DC',
                'category'    => 'volatility',
                'description' => 'Канал Дончиана: максимум и минимум за N периодов.',
                'params'      => [['name'=>'period','type'=>'int','default'=>20,'label'=>'Период']],
                'outputs'     => ['upper', 'middle', 'lower'],
            ],
            [
                'name'        => 'Standard Deviation',
                'short_name'  => 'STDDEV',
                'category'    => 'volatility',
                'description' => 'Стандартное отклонение цены за N периодов.',
                'params'      => [['name'=>'period','type'=>'int','default'=>20,'label'=>'Период']],
                'outputs'     => ['stddev'],
            ],

            // ── VOLUME ────────────────────────────────────────────────────
            [
                'name'        => 'OBV (On-Balance Volume)',
                'short_name'  => 'OBV',
                'category'    => 'volume',
                'description' => 'Баланс объёма. Накапливает объём при росте и вычитает при падении.',
                'params'      => [],
                'outputs'     => ['obv'],
            ],
            [
                'name'        => 'VWAP (Volume Weighted Average Price)',
                'short_name'  => 'VWAP',
                'category'    => 'volume',
                'description' => 'Средневзвешенная по объёму цена. Ориентир для институциональных трейдеров.',
                'params'      => [],
                'outputs'     => ['vwap'],
            ],
            [
                'name'        => 'Volume SMA',
                'short_name'  => 'VOL_SMA',
                'category'    => 'volume',
                'description' => 'Скользящая средняя объёма. Позволяет сравнивать текущий объём со средним.',
                'params'      => [['name'=>'period','type'=>'int','default'=>20,'label'=>'Период']],
                'outputs'     => ['vol_sma'],
            ],
            [
                'name'        => 'CMF (Chaikin Money Flow)',
                'short_name'  => 'CMF',
                'category'    => 'volume',
                'description' => 'Денежный поток Чайкина. Измеряет давление покупателей/продавцов по объёму.',
                'params'      => [['name'=>'period','type'=>'int','default'=>20,'label'=>'Период']],
                'outputs'     => ['cmf'],
            ],

            // ── PRICE ─────────────────────────────────────────────────────
            [
                'name'        => 'Price (OHLCV)',
                'short_name'  => 'PRICE',
                'category'    => 'price',
                'description' => 'Текущие значения цены: open, high, low, close, volume.',
                'params'      => [],
                'outputs'     => ['open', 'high', 'low', 'close', 'volume'],
            ],
            [
                'name'        => 'Candle Pattern',
                'short_name'  => 'CANDLE',
                'category'    => 'price',
                'description' => 'Паттерны свечей: Doji, Hammer, Engulfing и др. Возвращает 1 (паттерн есть) или 0.',
                'params'      => [
                    ['name'=>'pattern','type'=>'select','default'=>'doji','label'=>'Паттерн',
                     'options'=>['doji','hammer','inverted_hammer','bullish_engulfing','bearish_engulfing','morning_star','evening_star']],
                ],
                'outputs'     => ['pattern'],
            ],
        ];

        foreach ($indicators as $data) {
            Indicator::updateOrCreate(
                ['short_name' => $data['short_name']],
                $data
            );
        }
    }
}
