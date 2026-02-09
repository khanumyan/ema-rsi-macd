# Как проверить, работают ли команды

## Проблема: Нет логов

Если команды не создают логи, это может означать:
1. Команды не запускаются автоматически (нет cron задачи)
2. Команды запускаются, но есть ошибки
3. Логирование не настроено правильно

## Быстрая проверка

### 1. Проверьте расписание
```bash
php artisan schedule:list
```

Должно показать:
```
*/15 *    * * *  php artisan crypto:ema-rsi-macd --telegram  Next Due: X minutes from now
0    */12 * * *  php artisan signals:check-status  Next Due: X hours from now
```

### 2. Проверьте cron задачу
```bash
crontab -l
```

Должна быть строка:
```
* * * * * cd /home/ambrian/ema-rsi+macd && php artisan schedule:run >> /dev/null 2>&1
```

**ВАЖНО:** Если у вас указан другой путь (например, `/home/ambrian/signal-bot`), нужно исправить!

### 3. Исправьте cron задачу

Если cron указывает на другой проект:
```bash
# Откройте crontab
crontab -e

# Удалите старую строку и добавьте правильную:
* * * * * cd /home/ambrian/ema-rsi+macd && php artisan schedule:run >> /dev/null 2>&1

# Сохраните и проверьте
crontab -l
```

### 4. Проверьте логи вручную

#### Запустите команду вручную:
```bash
php artisan crypto:ema-rsi-macd --symbol=BTC
```

#### Проверьте логи:
```bash
# Общие логи Laravel
tail -50 storage/logs/laravel.log

# Логи команды (если настроены)
tail -20 storage/logs/crypto-signals.log
```

### 5. Используйте скрипт проверки

Запустите скрипт для автоматической проверки:
```bash
./check_commands.sh
```

## Что должно быть в логах

### При успешном запуске команды `crypto:ema-rsi-macd`:

В `storage/logs/laravel.log` должны быть записи:
```
[INFO] === EMA+RSI+MACD Command Started ===
[INFO] EMA+RSI+MACD: Starting analysis
[INFO] EMA+RSI+MACD: Analysis completed
[INFO] EMA+RSI+MACD: Starting Telegram sending phase
[INFO] EMA+RSI+MACD: Starting database save phase
[INFO] === EMA+RSI+MACD Command Completed ===
```

### При успешном запуске команды `signals:check-status`:

```
[INFO] === CheckSignalStatus Command Started ===
[INFO] CheckSignalStatus: Signals found
[INFO] CheckSignalStatus: Signal status updated
[INFO] === CheckSignalStatus Command Completed ===
```

## Тестирование без cron

Для тестирования без настройки cron:
```bash
# Запустит планировщик в реальном времени
php artisan schedule:work
```

Эта команда будет работать до тех пор, пока вы не остановите её (Ctrl+C).

## Проверка работы cron

### Проверьте, запускается ли schedule:run:
```bash
# Запустите вручную
php artisan schedule:run --verbose
```

Если показывает "No scheduled commands are ready to run", значит команды еще не должны запускаться по расписанию.

### Проверьте логи cron (если доступно):
```bash
# Ubuntu/Debian
grep CRON /var/log/syslog | tail -20

# CentOS/RHEL
grep CRON /var/log/cron | tail -20
```

## Проверка базы данных

Проверьте, сохраняются ли сигналы:
```bash
php artisan tinker
```

В tinker:
```php
// Последние 5 сигналов
App\Models\CryptoSignal::latest('created_at')->take(5)->get(['id', 'symbol', 'type', 'strength', 'created_at', 'signal_time']);

// Количество сигналов за сегодня
App\Models\CryptoSignal::whereDate('created_at', today())->count();
```

## Частые проблемы

### 1. Cron задача указывает на другой проект
**Решение:** Исправьте путь в crontab

### 2. Команды не запускаются
**Решение:** 
- Проверьте `php artisan schedule:list`
- Запустите `php artisan schedule:run` вручную
- Проверьте права доступа к файлам

### 3. Нет логов
**Решение:**
- Проверьте права на запись в `storage/logs/`
- Запустите команду вручную и проверьте логи
- Убедитесь, что логирование включено в `.env` (LOG_CHANNEL=stack)

### 4. Ошибки в логах
**Решение:**
- Проверьте `storage/logs/laravel.log` на наличие ошибок
- Исправьте ошибки и перезапустите команду

## Мониторинг в реальном времени

Для мониторинга логов в реальном времени:
```bash
# Все логи
tail -f storage/logs/laravel.log

# Только команды EMA+RSI+MACD
tail -f storage/logs/laravel.log | grep "EMA+RSI+MACD"

# Только команды CheckSignalStatus
tail -f storage/logs/laravel.log | grep "CheckSignalStatus"

# Только ошибки
tail -f storage/logs/laravel.log | grep "ERROR"
```

## Автоматическая проверка

Создайте cron задачу для проверки работы (опционально):
```bash
# Проверка каждые 5 минут, что команды работают
*/5 * * * * cd /home/ambrian/ema-rsi+macd && php artisan schedule:run >> /dev/null 2>&1 && echo "$(date): Schedule run OK" >> /tmp/schedule_check.log
```






