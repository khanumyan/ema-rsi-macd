<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class TelegramService
{
    private ?string $botToken;
    private ?string $chatId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
    }

    /**
     * Отправка сигнала в Telegram
     *
     * @param array $signal Данные сигнала
     * @param string $symbol Символ (BTCUSDT)
     * @param string $strategy Название стратегии
     * @return bool true если успешно отправлено
     */
    public function sendInstantSignal(array $signal, string $symbol, string $strategy = 'EMA+RSI+MACD'): bool
    {
        if (!$this->botToken || !$this->chatId) {
            \Log::warning('Telegram credentials not configured');
            return false;
        }

        try {
            $message = $this->formatSignalMessage($signal, $symbol, $strategy);

            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                return true;
            }

            \Log::error('Failed to send Telegram message', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (Exception $e) {
            \Log::error('Exception sending Telegram message', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Форматирование сообщения для Telegram
     */
    private function formatSignalMessage(array $signal, string $symbol, string $strategy): string
    {
        $type = $signal['type'];
        $strength = $signal['strength'];
        $price = number_format($signal['price'], 2, '.', ',');
        $rsi = number_format($signal['rsi'], 2);
        $ema = number_format($signal['ema'], 2, '.', ',');
        $macd = number_format($signal['macd'], 4);
        $macdHist = number_format($signal['macd_histogram'], 4);
        $stopLoss = $signal['stop_loss'] ? number_format($signal['stop_loss'], 2, '.', ',') : 'N/A';
        $takeProfit = $signal['take_profit'] ? number_format($signal['take_profit'], 2, '.', ',') : 'N/A';
        $longProb = $signal['long_probability'] ?? 0;
        $shortProb = $signal['short_probability'] ?? 0;
        $reason = $signal['reason'] ?? '';

        $emoji = $type === 'BUY' ? '🟢' : '🔴';
        $strengthEmoji = match ($strength) {
            'STRONG' => '🔥',
            'MEDIUM' => '⚡',
            'WEAK' => '💡',
            default => '📊',
        };

        $message = "<b>{$emoji} {$type} Signal - {$symbol}</b>\n";
        $message .= "<b>Strategy:</b> {$strategy}\n";
        $message .= "<b>Strength:</b> {$strengthEmoji} {$strength}\n\n";

        $message .= "<b>Price:</b> \${$price}\n";
        $message .= "<b>RSI:</b> {$rsi}\n";
        $message .= "<b>EMA(20):</b> \${$ema}\n";
        $message .= "<b>MACD:</b> {$macd}\n";
        $message .= "<b>MACD Histogram:</b> {$macdHist}\n\n";

        $message .= "<b>Stop Loss:</b> \${$stopLoss}\n";
        $message .= "<b>Take Profit:</b> \${$takeProfit}\n\n";

        $message .= "<b>Probabilities:</b>\n";
        $message .= "  BUY: {$longProb}%\n";
        $message .= "  SELL: {$shortProb}%\n\n";

        if ($reason) {
            $message .= "<b>Reason:</b> {$reason}\n";
        }

        return $message;
    }
}





