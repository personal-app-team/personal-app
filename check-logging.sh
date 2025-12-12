#!/bin/bash

echo "🔍 Проверка логирования ActivityLog в системе"
echo "============================================="
echo ""

# Запускаем детальный анализ
sail artisan logging:analyze:detailed

echo ""
echo "📋 Проверка выполненных миграций ActivityLog"
echo "============================================"
sail artisan migrate:status | grep -E "(activity_log|activitylog)"

echo ""
echo "📊 Проверка записей в логах"
echo "============================"
sail artisan tinker --execute="
echo 'Количество записей в activity_log: ' . \DB::table('activity_log')->count();
echo 'По дням за последние 7 дней:';
\DB::table('activity_log')
    ->where('created_at', '>=', \Carbon\Carbon::now()->subDays(7))
    ->select(\DB::raw('DATE(created_at) as date'), \DB::raw('count(*) as count'))
    ->groupBy('date')
    ->orderBy('date', 'desc')
    ->get()
    ->each(fn(\$row) => echo \$row->date . ': ' . \$row->count . ' записей\n');
"

echo ""
echo "✅ Проверка завершена"
