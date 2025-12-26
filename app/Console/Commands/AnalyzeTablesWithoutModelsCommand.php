<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AnalyzeTablesWithoutModelsCommand extends Command
{
    protected $signature = 'system:analyze-tables
                            {--orphans : Показать только таблицы без моделей}
                            {--models : Показать только модели без таблиц}
                            {--all : Показать полный отчет}';
    
    protected $description = 'Анализ таблиц без моделей и моделей без таблиц';

    public function handle()
    {
        $this->info('🔍 Анализ соответствия таблиц и моделей...');

        if ($this->option('orphans') || $this->option('all')) {
            $this->analyzeTablesWithoutModels();
        }
        
        if ($this->option('models') || $this->option('all')) {
            $this->analyzeModelsWithoutTables();
        }

        if (!$this->option('orphans') && !$this->option('models') && !$this->option('all')) {
            $this->analyzeTablesWithoutModels();
            $this->line('');
            $this->analyzeModelsWithoutTables();
        }

        return Command::SUCCESS;
    }

    private function analyzeTablesWithoutModels()
    {
        $this->info('📋 Таблицы без моделей:');

        // Получаем все таблицы из базы данных
        $tables = DB::select('SHOW TABLES');
        $databaseName = config('database.connections.mysql.database');
        $tableField = 'Tables_in_' . $databaseName;

        // Получаем все модели
        $modelFiles = File::allFiles(app_path('Models'));
        $models = [];
        
        foreach ($modelFiles as $file) {
            $models[] = strtolower($file->getFilenameWithoutExtension());
        }

        $tablesWithoutModels = [];
        $systemTables = [
            'migrations', 'cache', 'cache_locks', 'failed_jobs', 'jobs', 'job_batches',
            'sessions', 'password_reset_tokens', 'personal_access_tokens'
        ];

        foreach ($tables as $table) {
            $tableName = $table->$tableField;
            
            // Пропускаем системные таблицы
            if (in_array($tableName, $systemTables)) {
                continue;
            }

            // Проверяем, есть ли модель для таблицы
            $modelName = $this->tableToModelName($tableName);
            
            if (!in_array(strtolower($modelName), $models)) {
                $tablesWithoutModels[] = [
                    'table' => $tableName,
                    'expected_model' => $modelName,
                    'type' => $this->getTableType($tableName)
                ];
            }
        }

        if (empty($tablesWithoutModels)) {
            $this->info('   ✅ Все таблицы имеют соответствующие модели');
            return;
        }

        $this->table(['Таблица', 'Ожидаемая модель', 'Тип'], $tablesWithoutModels);
        
        $this->line("\n💡 Рекомендации:");
        foreach ($tablesWithoutModels as $item) {
            if ($item['type'] === 'Связующая (pivot)') {
                $this->line("   - Таблица '{$item['table']}' - связующая, модель не обязательна");
            } elseif ($item['type'] === 'Системная') {
                $this->line("   - Таблица '{$item['table']}' - системная, модель опциональна");
            } else {
                $this->warn("   - Создать модель для таблицы '{$item['table']}': php artisan make:model {$item['expected_model']}");
            }
        }
    }

    private function analyzeModelsWithoutTables()
    {
        $this->info('📁 Модели без таблиц:');

        $modelFiles = File::allFiles(app_path('Models'));
        $modelsWithoutTables = [];

        // Получаем все таблицы из базы данных
        $tables = DB::select('SHOW TABLES');
        $databaseName = config('database.connections.mysql.database');
        $tableField = 'Tables_in_' . $databaseName;
        $tableNames = array_map(fn($t) => $t->$tableField, $tables);

        foreach ($modelFiles as $file) {
            $modelName = $file->getFilenameWithoutExtension();
            $expectedTable = $this->modelToTableName($modelName);
            
            if (!in_array($expectedTable, $tableNames)) {
                // Проверяем, используется ли модель в ресурсах
                $usedInResources = $this->checkModelUsage($modelName);
                
                $modelsWithoutTables[] = [
                    'model' => $modelName,
                    'expected_table' => $expectedTable,
                    'used' => $usedInResources ? '✅ Да' : '⚠️ Нет'
                ];
            }
        }

        if (empty($modelsWithoutTables)) {
            $this->info('   ✅ Все модели имеют соответствующие таблицы');
            return;
        }

        $this->table(['Модель', 'Ожидаемая таблица', 'Используется'], $modelsWithoutTables);
        
        $this->line("\n💡 Рекомендации:");
        foreach ($modelsWithoutTables as $item) {
            if ($item['used'] === '⚠️ Нет') {
                $this->warn("   - Модель '{$item['model']}' не используется. Рассмотрите возможность удаления.");
            } else {
                $this->info("   - Создать таблицу для модели '{$item['model']}': php artisan make:migration create_{$item['expected_table']}_table");
            }
        }
    }

    private function tableToModelName(string $tableName): string
    {
        // Преобразуем snake_case в StudlyCase
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName)));
    }

    private function modelToTableName(string $modelName): string
    {
        // Преобразуем StudlyCase в snake_case и множественное число
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName));
        return str_ends_with($snake, 'y') 
            ? substr($snake, 0, -1) . 'ies' 
            : $snake . 's';
    }

    private function getTableType(string $tableName): string
    {
        if (str_contains($tableName, '_has_')) {
            return 'Связующая (pivot)';
        }
        
        if (in_array($tableName, ['activity_log', 'permissions', 'roles', 'model_has_permissions', 'model_has_roles', 'role_has_permissions'])) {
            return 'Системная';
        }
        
        return 'Основная';
    }

    private function checkModelUsage(string $modelName): bool
    {
        $resourcesPath = app_path('Filament/Resources');
        if (!File::exists($resourcesPath)) {
            return false;
        }

        $resourceFiles = File::allFiles($resourcesPath);
        
        foreach ($resourceFiles as $file) {
            $content = File::get($file->getPathname());
            if (str_contains($content, $modelName)) {
                return true;
            }
        }

        return false;
    }
}
