<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Запуск команды EMA+RSI+MACD каждые 15 минут
        // Это соответствует таймфрейму 15m
        $schedule->command('crypto:ema-rsi-macd')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('EMA+RSI+MACD command failed');
            })
            ->appendOutputTo(storage_path('logs/crypto-signals.log'));

        // Проверка статуса сигналов каждые 12 часов (в 00:00 и 12:00)
        // Проверяет сигналы от 12 до 36 часов назад
        $schedule->command('signals:check-status')
            ->cron('0 */12 * * *')  // Каждые 12 часов (00:00 и 12:00)
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('CheckSignalStatus command failed');
            })
            ->appendOutputTo(storage_path('logs/signal-status-check.log'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
