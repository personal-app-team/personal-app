<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

echo "🔍 Анализ таблиц без моделей...\n\n";

$tablesWithoutModels = [
    'activity_log',
    'address_project', 
    'mass_personnel_locations',
    'model_has_permissions',
    'model_has_permissions',
    'model_has_roles',
    'permissions',
    'personal_access_tokens',
    'project_assignments',
    'rates',
    'role_has_permissions',
    'roles',
    'shift_expenses',
    'user_specialties',
];

echo "📋 Таблиц без моделей: " . count($tablesWithoutModels) . "\n\n";

foreach ($tablesWithoutModels as $table) {
    try {
        $count = DB::table($table)->count();
        $columns = DB::select("SHOW COLUMNS FROM {$table}");
        
        echo "📊 Таблица: {$table}\n";
        echo "   Записей: {$count}\n";
        echo "   Колонок: " . count($columns) . "\n";
        
        // Определяем тип таблицы
        if (str_contains($table, 'permission') || str_contains($table, 'role')) {
            echo "   📌 Тип: Системная (Spatie Permission)\n";
        } elseif ($table === 'activity_log') {
            echo "   📌 Тип: Системная (Activity Log)\n";
        } elseif ($table === 'personal_access_tokens') {
            echo "   📌 Тип: Системная (Laravel Sanctum)\n";
        } elseif ($table === 'shift_expenses') {
            echo "   📌 Тип: Основная (замена expenses)\n";
        } else {
            echo "   📌 Тип: Возможно устаревшая\n";
        }
        
        echo "\n";
        
    } catch (\Exception $e) {
        echo "❌ Ошибка при анализе таблицы {$table}: " . $e->getMessage() . "\n\n";
    }
}

// Проверяем модели без таблиц
echo "\n🔍 Модели без таблиц:\n";
$modelsWithoutTables = ['contractor_workers', 'expenses'];

foreach ($modelsWithoutTables as $modelName) {
    $modelFile = __DIR__ . "/app/Models/{$modelName}.php";
    if (File::exists($modelFile)) {
        echo "📁 Модель: {$modelName}.php\n";
        echo "   Статус: Файл существует\n";
        
        // Проверяем, используется ли модель в ресурсах
        $resourcesPath = __DIR__ . '/app/Filament/Resources';
        $used = false;
        
        foreach (File::allFiles($resourcesPath) as $file) {
            if (strpos(File::get($file), $modelName) !== false) {
                $used = true;
                echo "   Используется в: " . $file->getFilename() . "\n";
            }
        }
        
        if (!$used) {
            echo "   ⚠️  Возможно не используется\n";
        }
        
        echo "\n";
    }
}
