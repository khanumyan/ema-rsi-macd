# Логирование команд

## Обзор

Обе команды (`crypto:ema-rsi-macd` и `signals:check-status`) ведут подробное логирование всех операций, ошибок и результатов работы.

## Расположение логов

Все логи сохраняются в `storage/logs/laravel.log` (стандартный лог Laravel).

## Формат логов

### EMA+RSI+MACD Command

#### Начало работы
```
[INFO] === EMA+RSI+MACD Command Started ===
{
    "started_at": "2026-02-04 13:00:00",
    "options": {
        "interval": "15m",
        "limit": 200,
        "telegram": true,
        "telegram_only": false
    }
}
```

#### Анализ символа
```
[INFO] EMA+RSI+MACD: Analyzing symbol
{
    "symbol": "BTCUSDT"
}

[INFO] EMA+RSI+MACD: Analysis completed
{
    "symbol": "BTCUSDT",
    "signal": "BUY",
    "strength": "STRONG",
    "price": 50000.00,
    "rsi": 35.5
}
```

#### Ошибки анализа
```
[ERROR] EMA+RSI+MACD: Error analyzing symbol
{
    "symbol": "ETHUSDT",
    "error": "Failed to fetch data: 500",
    "trace": "...",
    "file": "/path/to/file.php",
    "line": 123
}
```

#### Отправка в Telegram
```
[INFO] EMA+RSI+MACD: Starting Telegram sending phase

[INFO] EMA+RSI+MACD: Signal sent to Telegram
{
    "symbol": "BTCUSDT",
    "type": "BUY",
    "strength": "STRONG",
    "price": 50000.00
}

[INFO] EMA+RSI+MACD: Signal skipped (duplicate)
{
    "symbol": "BTCUSDT",
    "type": "BUY",
    "strength": "STRONG"
}

[ERROR] EMA+RSI+MACD: Failed to send signal to Telegram
{
    "symbol": "BTCUSDT",
    "type": "BUY"
}
```

#### Сохранение в базу данных
```
[INFO] EMA+RSI+MACD: Starting database save phase

[DEBUG] EMA+RSI+MACD: Signal saved to database
{
    "symbol": "BTCUSDT",
    "type": "BUY",
    "strength": "STRONG"
}

[DEBUG] EMA+RSI+MACD: Signal time set
{
    "signal_id": 123,
    "symbol": "BTCUSDT",
    "created_at": "2026-02-04 13:00:00",
    "signal_time": "2026-02-04 17:00:00"  // created_at + 4 часа
}

[ERROR] EMA+RSI+MACD: Error saving signal to database
{
    "symbol": "BTCUSDT",
    "type": "BUY",
    "error": "Database connection failed",
    "trace": "...",
    "file": "/path/to/file.php",
    "line": 456
}
```

#### Завершение работы
```
[INFO] === EMA+RSI+MACD Command Completed ===
{
    "ended_at": "2026-02-04 13:05:30",
    "execution_time_seconds": 330.5,
    "total_signals": 20,
    "success_count": 18,
    "error_count": 2
}
```

### CheckSignalStatus Command

#### Начало работы
```
[INFO] === CheckSignalStatus Command Started ===
{
    "started_at": "2026-02-04 13:00:00",
    "options": {
        "hours": 12,
        "range": 24
    }
}
```

#### Поиск сигналов
```
[INFO] CheckSignalStatus: Time range
{
    "from": "2026-02-03 01:00:00",
    "to": "2026-02-04 01:00:00",
    "hours_ago": 12,
    "range_hours": 24
}

[INFO] CheckSignalStatus: Signals found
{
    "count": 15,
    "symbols": ["BTCUSDT", "ETHUSDT", "BNBUSDT"]
}
```

#### Проверка сигнала
```
[DEBUG] CheckSignalStatus: Checking signal
{
    "signal_id": 123,
    "symbol": "BTCUSDT",
    "type": "BUY",
    "signal_time": "2026-02-03 17:00:00",
    "created_at": "2026-02-03 13:00:00"
}

[DEBUG] CheckSignalStatus: Fetching historical data
{
    "signal_id": 123,
    "symbol": "BTCUSDT",
    "signal_time": "2026-02-03 17:00:00",
    "start_time_ms": 1706976000000,
    "end_time_ms": 1707062400000
}

[DEBUG] CheckSignalStatus: Fetching klines (request #1)
{
    "symbol": "BTCUSDT",
    "interval": "15m",
    "start_time": 1706976000000,
    "end_time": 1707062400000
}

[INFO] CheckSignalStatus: BUY signal - TP hit
{
    "signal_id": 123,
    "symbol": "BTCUSDT",
    "kline_time": "2026-02-04 10:15:00",
    "high": 51000.00,
    "take_profit": 51000.00
}

[INFO] CheckSignalStatus: Signal status updated
{
    "signal_id": 123,
    "symbol": "BTCUSDT",
    "old_status": null,
    "new_status": "DONE"
}
```

#### Завершение работы
```
[INFO] === CheckSignalStatus Command Completed ===
{
    "ended_at": "2026-02-04 13:10:00",
    "execution_time_seconds": 600.0,
    "total_signals": 15,
    "done_count": 8,
    "missed_count": 4,
    "processing_count": 3,
    "error_count": 0
}
```

## Уровни логирования

- **INFO** - Основные события (начало/конец команды, успешные операции)
- **DEBUG** - Детальная информация (каждый шаг обработки)
- **WARNING** - Предупреждения (пропущенные сигналы, проблемы с API)
- **ERROR** - Ошибки (исключения, сбои)

## Просмотр логов

### В реальном времени
```bash
tail -f storage/logs/laravel.log
```

### Фильтрация по команде
```bash
# Логи EMA+RSI+MACD команды
grep "EMA+RSI+MACD" storage/logs/laravel.log

# Логи CheckSignalStatus команды
grep "CheckSignalStatus" storage/logs/laravel.log
```

### Только ошибки
```bash
grep "ERROR" storage/logs/laravel.log
```

### Последние 100 строк
```bash
tail -n 100 storage/logs/laravel.log
```

## Важные моменты

1. **signal_time = created_at + 4 часа**
   - При создании сигнала `signal_time` устанавливается автоматически
   - Это время используется для проверки статуса сигнала

2. **Детальное логирование ошибок**
   - Все ошибки логируются с полным trace
   - Указывается файл и строка, где произошла ошибка

3. **Производительность**
   - Логирование не блокирует выполнение команды
   - Используется асинхронное логирование Laravel

4. **Мониторинг**
   - По логам можно отследить:
     - Работают ли команды
     - Где происходят ошибки
     - Сколько сигналов обрабатывается
     - Время выполнения команд

## Примеры использования логов

### Проверка работы команды
```bash
# Проверить, запускалась ли команда сегодня
grep "EMA+RSI+MACD Command Started" storage/logs/laravel.log | grep "$(date +%Y-%m-%d)"
```

### Найти все ошибки за сегодня
```bash
grep "ERROR" storage/logs/laravel.log | grep "$(date +%Y-%m-%d)"
```

### Статистика по сигналам
```bash
# Сколько сигналов было отправлено в Telegram
grep "Signal sent to Telegram" storage/logs/laravel.log | wc -l

# Сколько сигналов было пропущено как дубликаты
grep "Signal skipped (duplicate)" storage/logs/laravel.log | wc -l
```

### Проверка времени выполнения
```bash
# Время выполнения последней команды
grep "Command Completed" storage/logs/laravel.log | tail -1 | grep -o '"execution_time_seconds":[0-9.]*'
```





