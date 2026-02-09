# Настройка автоматического запуска команд

## ✅ Расписание настроено

Расписание команд настроено в файле `bootstrap/app.php`:

1. **`crypto:ema-rsi-macd --telegram`** - каждые 15 минут
2. **`signals:check-status`** - каждые 12 часов (в 00:00 и 12:00)

## Проверка расписания

Проверить запланированные задачи:
```bash
php artisan schedule:list
```

Вывод:
```
*/15 *    * * *  php artisan crypto:ema-rsi-macd --telegram  Next Due: X minutes from now
0    */12 * * *  php artisan signals:check-status  Next Due: X hours from now
```

## Настройка Cron

Для автоматического запуска команд нужно добавить в crontab:

### Шаг 1: Откройте crontab
```bash
crontab -e
```

### Шаг 2: Добавьте строку
```bash
* * * * * cd /home/ambrian/ema-rsi+macd && php artisan schedule:run >> /dev/null 2>&1
```

### Шаг 3: Сохраните и проверьте
```bash
# Проверить, что cron установлен
crontab -l
```

## Как это работает

1. **Cron запускает `schedule:run` каждую минуту**
2. **Laravel проверяет расписание** и запускает команды, если пришло время
3. **Команды выполняются автоматически** согласно расписанию

## Тестирование локально

Для тестирования планировщика без cron:
```bash
php artisan schedule:work
```

Эта команда будет запускать планировщик в реальном времени (как cron, но вручную).

## Расписание команд

### crypto:ema-rsi-macd --telegram
- **Частота:** каждые 15 минут
- **Описание:** Анализ криптовалют по стратегии EMA+RSI+MACD и отправка сигналов в Telegram
- **Логи:** `storage/logs/crypto-signals.log`

### signals:check-status
- **Частота:** каждые 12 часов (00:00 и 12:00)
- **Описание:** Проверка статуса сигналов (DONE/MISSED/PROCESSING)
- **Логи:** `storage/logs/signal-status-check.log`

## Изменение расписания

Расписание можно изменить в файле `bootstrap/app.php`:

```php
->withSchedule(function (Schedule $schedule): void {
    // Изменить частоту запуска
    $schedule->command('crypto:ema-rsi-macd --telegram')
        ->everyFiveMinutes()  // Вместо everyFifteenMinutes()
        ->withoutOverlapping();
        
    // Или использовать cron формат
    $schedule->command('signals:check-status')
        ->cron('0 */6 * * *')  // Каждые 6 часов
        ->withoutOverlapping();
})
```

## Доступные методы расписания

- `->everyMinute()` - каждую минуту
- `->everyFiveMinutes()` - каждые 5 минут
- `->everyFifteenMinutes()` - каждые 15 минут
- `->everyThirtyMinutes()` - каждые 30 минут
- `->hourly()` - каждый час
- `->daily()` - каждый день в 00:00
- `->twiceDaily(1, 13)` - дважды в день (в 01:00 и 13:00)
- `->cron('0 */12 * * *')` - каждые 12 часов (00:00 и 12:00)

## Проверка работы

### Проверить, что cron работает:
```bash
# Посмотреть логи cron (если доступно)
grep CRON /var/log/syslog | tail -20
```

### Проверить выполнение команд:
```bash
# Логи команды EMA+RSI+MACD
tail -f storage/logs/crypto-signals.log

# Логи команды CheckSignalStatus
tail -f storage/logs/signal-status-check.log

# Общие логи Laravel
tail -f storage/logs/laravel.log
```

## Устранение проблем

### Команды не запускаются

1. **Проверьте cron:**
   ```bash
   crontab -l
   ```

2. **Проверьте расписание:**
   ```bash
   php artisan schedule:list
   ```

3. **Проверьте логи:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Запустите вручную для теста:**
   ```bash
   php artisan crypto:ema-rsi-macd --telegram
   php artisan signals:check-status
   ```

### Команда выполняется слишком долго

Если команда выполняется дольше, чем интервал между запусками, используйте `->withoutOverlapping()` (уже добавлено), чтобы предотвратить одновременный запуск нескольких экземпляров.

## Важно

- Убедитесь, что путь в crontab правильный: `/home/ambrian/ema-rsi+macd`
- Убедитесь, что PHP доступен из командной строки
- Проверьте права доступа к файлам и директориям





