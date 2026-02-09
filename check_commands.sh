#!/bin/bash

# Скрипт для проверки работы команд

echo "=========================================="
echo "Проверка работы команд EMA+RSI+MACD"
echo "=========================================="
echo ""

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Проверка 1: Расписание
echo -e "${YELLOW}1. Проверка расписания:${NC}"
php artisan schedule:list
echo ""

# Проверка 2: Последние логи
echo -e "${YELLOW}2. Последние 20 строк логов Laravel:${NC}"
tail -20 storage/logs/laravel.log 2>/dev/null || echo "Лог файл не найден"
echo ""

# Проверка 3: Логи команд
echo -e "${YELLOW}3. Логи команды crypto:ema-rsi-macd:${NC}"
if [ -f storage/logs/crypto-signals.log ]; then
    tail -10 storage/logs/crypto-signals.log
else
    echo "Файл логов не найден (команда еще не запускалась)"
fi
echo ""

echo -e "${YELLOW}4. Логи команды signals:check-status:${NC}"
if [ -f storage/logs/signal-status-check.log ]; then
    tail -10 storage/logs/signal-status-check.log
else
    echo "Файл логов не найден (команда еще не запускалась)"
fi
echo ""

# Проверка 5: Cron задача
echo -e "${YELLOW}5. Проверка cron задачи:${NC}"
if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    echo -e "${GREEN}✓ Cron задача найдена:${NC}"
    crontab -l | grep "schedule:run"
else
    echo -e "${RED}✗ Cron задача НЕ найдена!${NC}"
    echo "Добавьте в crontab:"
    echo "* * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"
fi
echo ""

# Проверка 6: Последние записи в БД
echo -e "${YELLOW}6. Последние 5 сигналов в базе данных:${NC}"
php artisan tinker --execute="echo App\Models\CryptoSignal::latest('created_at')->take(5)->get(['id', 'symbol', 'type', 'strength', 'created_at', 'signal_time', 'status'])->toJson(JSON_PRETTY_PRINT);" 2>/dev/null || echo "Не удалось получить данные из БД"
echo ""

# Проверка 7: Тестовый запуск schedule:run
echo -e "${YELLOW}7. Тестовый запуск schedule:run:${NC}"
php artisan schedule:run --verbose 2>&1 | head -10
echo ""

echo "=========================================="
echo "Проверка завершена"
echo "=========================================="





