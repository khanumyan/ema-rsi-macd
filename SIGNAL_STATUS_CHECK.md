# Команда проверки статуса сигналов

## Описание

Команда `signals:check-status` проверяет статус сигналов на основе исторических данных цены и определяет, был ли достигнут Take Profit (DONE) или Stop Loss (MISSED).

## Использование

### Базовый запуск (проверка сигналов от 12 до 36 часов назад):
```bash
php artisan signals:check-status
```

### Проверка сигналов за другой период:
```bash
# Проверка сигналов от 24 до 48 часов назад
php artisan signals:check-status --hours=24 --range=24
```

### Параметры:
- `--hours` - Количество часов назад для начала проверки (по умолчанию: 12)
- `--range` - Диапазон проверки в часах (по умолчанию: 24)

## Как это работает

1. **Поиск сигналов:**
   - Ищет сигналы типа BUY или SELL
   - Без статуса (NULL) или со статусом PROCESSING
   - В указанном временном диапазоне
   - С установленными stop_loss и take_profit

2. **Получение исторических данных:**
   - Запрашивает исторические свечи с Binance Futures API
   - Использует интервал из сигнала (по умолчанию 15m)
   - Получает данные от времени создания сигнала до текущего момента

3. **Проверка статуса:**
   - Проверяет каждую свечу в хронологическом порядке
   - Для BUY сигналов:
     - Если `low <= stop_loss` → **MISSED**
     - Если `high >= take_profit` → **DONE**
   - Для SELL сигналов:
     - Если `high >= stop_loss` → **MISSED**
     - Если `low <= take_profit` → **DONE**
   - Если ни TP, ни SL не достигнуты → **PROCESSING**

## Статусы сигналов

| Статус | Описание |
|--------|----------|
| **DONE** | Take Profit был достигнут (успешная сделка) |
| **MISSED** | Stop Loss был достигнут (убыточная сделка) |
| **PROCESSING** | Ни TP, ни SL еще не достигнуты (сделка в процессе) |

## Автоматический запуск

Команда настроена на автоматический запуск **каждые 12 часов** через Laravel Scheduler.

Для работы автоматического запуска нужно добавить в crontab:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Логи

Логи команды сохраняются в:
- `storage/logs/signal-status-check.log` - вывод команды
- `storage/logs/laravel.log` - ошибки и предупреждения

## Пример вывода

```
🔍 Checking signal statuses...
📅 Checking signals from 2026-02-03 13:00:00 to 2026-02-04 01:00:00
📊 Found 15 signals to check
✅ Status check complete!
┌─────────────┬───────┐
│ Status      │ Count │
├─────────────┼───────┤
│ DONE        │ 8     │
│ MISSED      │ 4     │
│ PROCESSING  │ 3     │
│ ERRORS      │ 0     │
└─────────────┴───────┘
```

## Важные детали

1. **Время сигнала:**
   - Используется поле `signal_time` если оно установлено
   - Иначе используется `created_at`

2. **Обработка ошибок:**
   - Если не удалось получить исторические данные, статус остается PROCESSING
   - Ошибки логируются в `storage/logs/laravel.log`

3. **Rate Limiting:**
   - Между запросами к Binance API есть задержка 0.1 секунды
   - Между проверками сигналов задержка 0.2 секунды

4. **Точность проверки:**
   - Проверка основана на high/low каждой свечи
   - Если свеча открылась до сигнала, но закрылась после, проверяются её high/low
   - Если в одной свече достигнуты и TP, и SL, приоритет у SL (MISSED)

## Использование данных для анализа

После проверки статусов можно анализировать:

1. **Эффективность стратегии:**
   ```sql
   SELECT 
       type,
       strength,
       COUNT(*) as total,
       SUM(CASE WHEN status = 'DONE' THEN 1 ELSE 0 END) as done,
       SUM(CASE WHEN status = 'MISSED' THEN 1 ELSE 0 END) as missed,
       ROUND(SUM(CASE WHEN status = 'DONE' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as win_rate
   FROM crypto_signals
   WHERE status IN ('DONE', 'MISSED')
   GROUP BY type, strength;
   ```

2. **Средняя вероятность успешных сигналов:**
   ```sql
   SELECT 
       AVG(long_probability) as avg_long_prob,
       AVG(short_probability) as avg_short_prob
   FROM crypto_signals
   WHERE status = 'DONE';
   ```

3. **Время до достижения TP/SL:**
   ```sql
   SELECT 
       AVG(TIMESTAMPDIFF(HOUR, signal_time, updated_at)) as avg_hours_to_close
   FROM crypto_signals
   WHERE status IN ('DONE', 'MISSED')
   AND signal_time IS NOT NULL;
   ```













