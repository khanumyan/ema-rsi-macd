#!/usr/bin/env php
<?php

/**
 * Скрипт для диагностики проблем с расписанием Laravel
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Диагностика расписания Laravel ===\n\n";

// 1. Проверка мьютексов в базе данных
echo "1. Проверка мьютексов в базе данных:\n";
try {
    $locks = DB::table('cache_locks')->get();
    if ($locks->isEmpty()) {
        echo "   ✓ Мьютексов нет\n";
    } else {
        echo "   ⚠ Найдено мьютексов: " . $locks->count() . "\n";
        foreach ($locks as $lock) {
            $expired = $lock->expiration < time();
            $status = $expired ? "ИСТЕК" : "АКТИВЕН";
            echo "   - Ключ: {$lock->key}\n";
            echo "     Владелец: {$lock->owner}\n";
            echo "     Истекает: " . date('Y-m-d H:i:s', $lock->expiration) . " ({$status})\n";
            if ($expired) {
                echo "     ⚠ Этот мьютекс истек и должен быть удален\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "   ✗ Ошибка при проверке мьютексов: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Проверка последних запусков команд
echo "2. Последние записи в логах:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20);
    $found = false;
    foreach (array_reverse($lastLines) as $line) {
        if (strpos($line, 'EMA+RSI+MACD Command') !== false) {
            echo "   " . trim($line) . "\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "   ⚠ Записей о команде не найдено\n";
    }
} else {
    echo "   ⚠ Файл логов не найден\n";
}

echo "\n";

// 3. Проверка расписания
echo "3. Текущее расписание:\n";
$schedule = app(Illuminate\Console\Scheduling\Schedule::class);
$events = $schedule->events();
foreach ($events as $event) {
    $command = $event->command;
    $mutex = $event->mutex;
    $mutexName = $event->mutexName();
    
    echo "   Команда: {$command}\n";
    echo "   Мьютекс: {$mutexName}\n";
    
    // Проверяем, заблокирован ли мьютекс
    try {
        $isLocked = $mutex->exists($event);
        echo "   Статус: " . ($isLocked ? "🔒 ЗАБЛОКИРОВАН" : "✓ СВОБОДЕН") . "\n";
        
        if ($isLocked) {
            // Пытаемся получить информацию о мьютексе
            $lock = DB::table('cache_locks')->where('key', $mutexName)->first();
            if ($lock) {
                $expired = $lock->expiration < time();
                echo "   Владелец: {$lock->owner}\n";
                echo "   Истекает: " . date('Y-m-d H:i:s', $lock->expiration);
                if ($expired) {
                    echo " (ИСТЕК - можно удалить)\n";
                } else {
                    echo "\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "   Ошибка проверки: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "=== Конец диагностики ===\n";


