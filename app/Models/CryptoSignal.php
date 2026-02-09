<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CryptoSignal extends Model
{
    protected $fillable = [
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
        'volume_ratio',
        'htf_trend',
        'htf_rsi',
        'ltf_rsi',
        'long_score',
        'short_score',
        'long_probability',
        'short_probability',
        'interval',
        'limit',
        'reason',
        'sent_to_telegram',
        'signal_time',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'rsi' => 'decimal:4',
        'ema' => 'decimal:8',
        'ema_slow' => 'decimal:8',
        'macd' => 'decimal:8',
        'macd_signal' => 'decimal:8',
        'macd_histogram' => 'decimal:8',
        'atr' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'take_profit' => 'decimal:8',
        'volume_ratio' => 'decimal:4',
        'htf_rsi' => 'decimal:4',
        'ltf_rsi' => 'decimal:4',
        'long_score' => 'integer',
        'short_score' => 'integer',
        'long_probability' => 'integer',
        'short_probability' => 'integer',
        'limit' => 'integer',
        'sent_to_telegram' => 'boolean',
        'signal_time' => 'datetime',
    ];

    /**
     * Сохранить сигнал в базу данных
     */
    public static function saveSignal(array $data): self
    {
        return self::create($data);
    }

    /**
     * Проверка, нужно ли отправлять сигнал (проверка на дубликаты)
     * 
     * @param string $symbol Символ (BTCUSDT)
     * @param string $type Тип сигнала (BUY/SELL)
     * @param string $strength Сила сигнала (STRONG/MEDIUM/WEAK)
     * @param string $strategy Название стратегии
     * @param float|null $rsi Значение RSI для дополнительной фильтрации
     * @return bool true если нужно отправить, false если дубликат
     */
    public static function shouldSendSignal(
        string $symbol,
        string $type,
        string $strength,
        string $strategy = 'EMA+RSI+MACD',
        ?float $rsi = null
    ): bool {
        // Определяем временной лимит в зависимости от силы сигнала
        $timeLimit = match ($strength) {
            'STRONG' => 90,  // 90 минут для STRONG
            'MEDIUM' => 120, // 120 минут для MEDIUM
            'WEAK' => 180,   // 180 минут для WEAK
            default => 120,
        };

        $cutoffTime = Carbon::now()->subMinutes($timeLimit);

        // Проверяем, был ли недавно отправлен похожий сигнал
        $query = self::where('symbol', $symbol)
            ->where('type', $type)
            ->where('strategy', $strategy)
            ->where('strength', $strength)
            ->where('sent_to_telegram', true)
            ->where('created_at', '>=', $cutoffTime);

        // Дополнительная фильтрация по RSI, если указан
        if ($rsi !== null) {
            // Если RSI в зоне перекупленности/перепроданности, более строгая проверка
            if ($rsi <= 30 || $rsi >= 70) {
                $query->whereBetween('rsi', [$rsi - 5, $rsi + 5]);
            }
        }

        // Если есть похожий сигнал - не отправляем
        return !$query->exists();
    }
}
