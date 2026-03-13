<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Запуск команды EMA+RSI+MACD каждые 15 минут
        // Это соответствует таймфрейму 15m
        $schedule->command('crypto:ema-rsi-macd')
            ->everyFifteenMinutes()
            ->withoutOverlapping(60) // Таймаут 60 минут - если команда выполняется дольше, мьютекс снимается
            ->onFailure(function () {
                \Log::error('EMA+RSI+MACD command failed', [
                    'timestamp' => now()->toDateTimeString(),
                ]);
            })
            ->appendOutputTo(storage_path('logs/crypto-signals.log'));

        // Проверка статуса сигналов каждые 12 часов
        // Проверяет сигналы от 12 до 36 часов назад
        $schedule->command('signals:check-status')
            ->everyTwelveHours()
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('CheckSignalStatus command failed');
            })
            ->appendOutputTo(storage_path('logs/signal-status-check.log'));

        // Обновление времени свечи для сигналов со статусом DONE/MISSED
        // Запускается каждый час, чтобы обновить updated_at для завершенных сигналов
        $schedule->command('signals:update-candle-time')
            ->hourly()
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('UpdateSignalCandleTime command failed');
            })
            ->appendOutputTo(storage_path('logs/update-candle-time.log'));

        // Альтернативный вариант: запуск в конкретное время
        // Например, каждые 15 минут в рабочее время (8:00 - 22:00 UTC)
        // $schedule->command('crypto:ema-rsi-macd --telegram')
        //     ->cron('*/15 8-22 * * *')
        //     ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

