# Инструкция по использованию команды EMA+RSI+MACD

## Установка

1. **Выполните миграцию базы данных:**
```bash
php artisan migrate
```

2. **Настройте переменные окружения в `.env`:**
```env
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_CHAT_ID=your_chat_id_here
```

3. **Настройте список символов в `config/crypto_symbols.php`** (опционально)

## Использование команды

### Базовый запуск (только анализ, без отправки в Telegram):
```bash
php artisan crypto:ema-rsi-macd
```

### Запуск с отправкой в Telegram:
```bash
php artisan crypto:ema-rsi-macd --telegram
```

### Запуск только с отправкой в Telegram (без вывода в консоль):
```bash
php artisan crypto:ema-rsi-macd --telegram-only
```

### Анализ конкретного символа:
```bash
php artisan crypto:ema-rsi-macd --symbol=BTC --telegram
```

### Анализ нескольких символов:
```bash
php artisan crypto:ema-rsi-macd --symbol=BTC --symbol=ETH --symbol=BNB --telegram
```

### Изменение таймфрейма:
```bash
php artisan crypto:ema-rsi-macd --interval=1h --telegram
```

### Изменение количества свечей:
```bash
php artisan crypto:ema-rsi-macd --limit=500 --telegram
```

## Автоматический запуск через Cron

См. файл `CRON_SETUP.md` для подробных инструкций.

Краткая версия:
1. Добавьте в crontab: `* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1`
2. Команда будет запускаться автоматически каждые 15 минут

## Структура базы данных

Все сигналы сохраняются в таблице `crypto_signals` со следующими полями:

- **Основные данные**: symbol, strategy, type (BUY/SELL/HOLD), strength (STRONG/MEDIUM/WEAK)
- **Цены**: price, stop_loss, take_profit
- **Индикаторы EMA**: ema (EMA20), ema_slow (EMA50)
- **RSI**: rsi
- **MACD**: macd (MACD Line), macd_signal (Signal Line), macd_histogram (Histogram)
- **ATR**: atr
- **Баллы и вероятности**: long_score, short_score, long_probability, short_probability
- **Параметры**: interval, limit
- **Метаданные**: reason, sent_to_telegram

## Фильтры сигналов

Команда применяет следующие фильтры перед отправкой в Telegram:

1. **Strength Filter**: Отправляются только STRONG и MEDIUM сигналы
2. **Market Context Filter**: Проверка волатильности BTC
   - Если BTC волатильность > 3% за 15m → все сигналы блокируются
   - Если BTC падает > 1% за 15m → SELL сигналы для альтов блокируются
3. **Duplicate Filter**: Проверка на дубликаты
   - STRONG сигналы: не чаще чем раз в 90 минут
   - MEDIUM сигналы: не чаще чем раз в 120 минут

## Логи

- Общие логи: `storage/logs/laravel.log`
- Логи команды: `storage/logs/crypto-signals.log`

## Проверка работы

Для тестирования планировщика:
```bash
php artisan schedule:work
```

Для просмотра запланированных задач:
```bash
php artisan schedule:list
```

## Примеры использования

### Ежедневный анализ всех символов:
```bash
php artisan crypto:ema-rsi-macd --telegram
```

### Анализ только BTC с таймфреймом 1 час:
```bash
php artisan crypto:ema-rsi-macd --symbol=BTC --interval=1h --telegram
```

### Анализ с большим количеством свечей для более точных расчетов:
```bash
php artisan crypto:ema-rsi-macd --limit=500 --telegram
```






