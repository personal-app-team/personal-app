#!/bin/bash

echo "========================================"
echo "   ПОЛНАЯ ПРОВЕРКА СИСТЕМЫ"
echo "========================================"

echo ""
echo "1. Проверяем статус миграций..."
./vendor/bin/sail artisan migrate:status | grep -E "(Pending|Ran)" | head -5

echo ""
echo "2. Проверяем ресурсы на устаревшие отношения..."
./vendor/bin/sail artisan app:check-broken-relations

echo ""
echo "3. Проверяем модели на устаревшие отношения..."
./vendor/bin/sail artisan app:check-model-relations

echo ""
echo "4. Проверяем таблицы без моделей..."
./vendor/bin/sail artisan tinker --execute="
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

\$tables = DB::select('SHOW TABLES');
\$dbTables = array_map(function(\$item) {
    return array_values((array)\$item)[0];
}, \$tables);

\$models = [];
foreach (glob('app/Models/*.php') as \$modelFile) {
    \$modelName = 'App\\\\Models\\\\' . basename(\$modelFile, '.php');
    if (class_exists(\$modelName) && method_exists(\$modelName, 'getTable')) {
        \$models[] = (new \$modelName)->getTable();
    }
}

echo 'Таблицы без моделей:\n';
\$found = false;
foreach (\$dbTables as \$table) {
    if (!in_array(\$table, \$models) && \$table != 'migrations') {
        echo '❌ ' . \$table . '\n';
        \$found = true;
    }
}
if (!\$found) {
    echo '✅ Все таблицы имеют модели\n';
}
"

echo ""
echo "========================================"
echo "   ПРОВЕРКА ЗАВЕРШЕНА"
echo "========================================"
