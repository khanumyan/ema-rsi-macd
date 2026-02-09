# Настройка Cron для команд EMA+RSI+MACD и CheckSignalStatus

## Автоматический запуск команд

### Вариант 1: Использование Laravel Scheduler (Рекомендуется)

Laravel имеет встроенный планировщик задач. Для его работы нужно добавить одну строку в crontab:

```bash
* * * * * cd /home/ambrian/ema-rsi+macd && php artisan schedule:run >> /dev/null 2>&1
```

Эта команда будет запускаться каждую минуту и проверять, нужно ли выполнить запланированные задачи.

**Запланированные команды:**
- `crypto:ema-rsi-macd --telegram` - каждые 15 минут
- `signals:check-status` - дважды в день (в 01:00 и 13:00)

Расписание настроено в файле `bootstrap/app.php`.

### Вариант 2: Прямой запуск через Cron

Если вы хотите запускать команду напрямую через cron, добавьте в crontab:

```bash
# Запуск каждые 15 минут
*/15 * * * * cd /home/ambrian/ema-rsi+macd && php artisan crypto:ema-rsi-macd --telegram >> /dev/null 2>&1
```

Или в конкретное время (например, каждые 15 минут с 8:00 до 22:00):

```bash
*/15 8-22 * * * cd /home/ambrian/ema-rsi+macd && php artisan crypto:ema-rsi-macd --telegram >> /dev/null 2>&1
```

### Установка Cron

1. Откройте crontab для редактирования:
```bash
crontab -e
```

2. Добавьте одну из строк выше

3. Сохраните и закройте редактор

4. Проверьте, что cron установлен:
```bash
crontab -l
```

### Проверка работы

Для тестирования планировщика локально:
```bash
php artisan schedule:work
```

Для проверки, какие задачи запланированы:
```bash
php artisan schedule:list
```

### Логи

Логи команды сохраняются в:
- `storage/logs/laravel.log` - общие логи Laravel
- `storage/logs/crypto-signals.log` - логи команды EMA+RSI+MACD

### Переменные окружения

Убедитесь, что в `.env` файле настроены:
```
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id
```

### Настройка времени запуска

Вы можете изменить расписание в файле `app/Console/Kernel.php`:

```php
// Каждые 15 минут
$schedule->command('crypto:ema-rsi-macd --telegram')
    ->everyFifteenMinutes();

// Каждые 5 минут
$schedule->command('crypto:ema-rsi-macd --telegram')
    ->everyFiveMinutes();

// В конкретное время (например, каждые 15 минут с 8:00 до 22:00)
$schedule->command('crypto:ema-rsi-macd --telegram')
    ->cron('*/15 8-22 * * *');

// Только в рабочие дни
$schedule->command('crypto:ema-rsi-macd --telegram')
    ->everyFifteenMinutes()
    ->weekdays();
```

