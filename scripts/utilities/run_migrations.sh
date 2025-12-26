#!/bin/bash

echo "🚀 Запуск миграций в правильном порядке..."

# 1. Финализируем pending миграции
echo "📦 1. Финализация pending миграций..."
sail artisan migrate --path=database/migrations/2025_12_10_180000_finalize_pending_migrations.php

# 2. Удаляем неиспользуемые таблицы
echo "🗑️ 2. Удаление неиспользуемых таблиц..."
sail artisan migrate --path=database/migrations/2025_12_10_152447_drop_unused_tables.php

# 3. Преобразуем shift_expenses в expenses
echo "🔄 3. Преобразование shift_expenses в expenses..."
sail artisan migrate --path=database/migrations/2025_12_10_170000_convert_shift_expenses_to_expenses.php

# 4. Проверяем статус
echo "📊 4. Проверка статуса миграций..."
sail artisan migrate:status

echo "✅ Все миграции выполнены!"
