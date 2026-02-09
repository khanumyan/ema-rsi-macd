#!/bin/bash

# Скрипт для исправления cron задачи

echo "Исправление cron задачи..."
echo ""

# Получаем текущий crontab
CURRENT_CRON=$(crontab -l 2>/dev/null)

# Проверяем, есть ли уже правильная задача
if echo "$CURRENT_CRON" | grep -q "ema-rsi+macd"; then
    echo "✓ Cron задача уже настроена правильно!"
    crontab -l | grep "ema-rsi+macd"
    exit 0
fi

# Создаем временный файл
TEMP_CRON=$(mktemp)

# Если есть старый crontab, копируем его (исключая старые задачи schedule:run)
if [ -n "$CURRENT_CRON" ]; then
    echo "$CURRENT_CRON" | grep -v "schedule:run" | grep -v "signal-bot" > "$TEMP_CRON"
fi

# Добавляем правильную задачу
echo "* * * * * cd /home/ambrian/ema-rsi+macd && php artisan schedule:run >> /dev/null 2>&1" >> "$TEMP_CRON"

# Устанавливаем новый crontab
crontab "$TEMP_CRON"

# Удаляем временный файл
rm "$TEMP_CRON"

echo "✓ Cron задача успешно обновлена!"
echo ""
echo "Текущий crontab:"
crontab -l
echo ""
echo "Проверка:"
crontab -l | grep "ema-rsi+macd" && echo "✓ Задача найдена!" || echo "✗ Задача не найдена!"





