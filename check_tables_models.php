<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

echo "🔍 Сравнение таблиц и моделей...\n\n";

// Получаем все таблицы
$tables = DB::select('SHOW TABLES');
$dbTables = array_map(function($row) {
    return current((array)$row);
}, $tables);

echo "📊 Таблиц в БД: " . count($dbTables) . "\n";

// Получаем все модели
$modelFiles = File::files(__DIR__ . '/app/Models');
$modelTables = [];

foreach ($modelFiles as $file) {
    $className = 'App\\Models\\' . $file->getBasename('.php');
    
    if (class_exists($className)) {
        try {
            $model = new $className;
            if (method_exists($model, 'getTable')) {
                $modelTables[] = $model->getTable();
            }
        } catch (\Exception $e) {
            // Пропускаем модели, которые не могут быть инстанциированы
        }
    }
}

echo "📁 Моделей: " . count($modelTables) . "\n\n";

// Таблицы без моделей
$tablesWithoutModels = array_diff($dbTables, $modelTables);
$tablesWithoutModels = array_filter($tablesWithoutModels, function($table) {
    // Исключаем системные таблицы
    return !in_array($table, [
        'migrations', 'cache', 'cache_locks', 'job_batches', 'jobs',
        'failed_jobs', 'sessions', 'password_reset_tokens'
    ]);
});

if (!empty($tablesWithoutModels)) {
    echo "❌ Таблицы без моделей:\n";
    foreach ($tablesWithoutModels as $table) {
        echo "   - {$table}\n";
    }
} else {
    echo "✅ Все таблицы имеют модели\n";
}

// Модели без таблиц (потенциально устаревшие)
$modelsWithoutTables = array_diff($modelTables, $dbTables);
if (!empty($modelsWithoutTables)) {
    echo "\n⚠️  Модели без таблиц (возможно устаревшие):\n";
    foreach ($modelsWithoutTables as $table) {
        echo "   - {$table}\n";
    }
}
