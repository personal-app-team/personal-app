<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;

class ListPermissions extends Command
{
    protected $signature = 'app:list-permissions {--generate : Сгенерировать недостающие разрешения}';
    protected $description = 'Показать все разрешения системы и их описание';

    public function handle()
    {
        $models = $this->getAllModels();
        $permissions = Permission::all()->pluck('name')->toArray();
        
        $this->info("📋 СИСТЕМА РАЗРЕШЕНИЙ");
        $this->info("======================");
        
        $this->newLine();
        $this->info("🔍 Существующие разрешения в базе:");
        $this->newLine();
        
        foreach ($permissions as $permission) {
            $this->line("• {$permission}");
        }
        
        $this->newLine();
        $this->info("📊 Анализ по моделям:");
        $this->newLine();
        
        $tableData = [];
        
        foreach ($models as $model) {
            $modelName = strtolower(class_basename($model));
            $expectedPermissions = [
                "view_any_{$modelName}",
                "view_{$modelName}",
                "create_{$modelName}",
                "update_{$modelName}",
                "delete_{$modelName}",
                "delete_any_{$modelName}",
                "restore_{$modelName}",
                "restore_any_{$modelName}",
                "force_delete_{$modelName}",
                "force_delete_any_{$modelName}",
                "replicate_{$modelName}",
            ];
            
            $existing = [];
            $missing = [];
            
            foreach ($expectedPermissions as $perm) {
                if (in_array($perm, $permissions)) {
                    $existing[] = $perm;
                } else {
                    $missing[] = $perm;
                }
            }
            
            $tableData[] = [
                'Модель' => $model,
                'Найдено' => count($existing),
                'Отсутствует' => count($missing),
                'Статус' => count($missing) > 0 ? '⚠️ Неполный' : '✅ Полный',
            ];
        }
        
        $this->table(
            ['Модель', 'Найдено', 'Отсутствует', 'Статус'],
            $tableData
        );
        
        if ($this->option('generate')) {
            $this->generateMissingPermissions($models, $permissions);
        }
        
        return Command::SUCCESS;
    }
    
    private function getAllModels(): array
    {
        $models = [];
        $modelFiles = File::allFiles(app_path('Models'));
        
        foreach ($modelFiles as $file) {
            $className = 'App\\Models\\' . $file->getFilenameWithoutExtension();
            if (class_exists($className)) {
                $models[] = $className;
            }
        }
        
        return $models;
    }
    
    private function generateMissingPermissions(array $models, array $existingPermissions): void
    {
        $this->info("🔧 Генерация недостающих разрешений...");
        
        $created = 0;
        
        foreach ($models as $model) {
            $modelName = strtolower(class_basename($model));
            $permissions = [
                "view_any_{$modelName}" => "Просмотр списка " . class_basename($model),
                "view_{$modelName}" => "Просмотр записи " . class_basename($model),
                "create_{$modelName}" => "Создание " . class_basename($model),
                "update_{$modelName}" => "Редактирование " . class_basename($model),
                "delete_{$modelName}" => "Удаление " . class_basename($model),
                "delete_any_{$modelName}" => "Массовое удаление " . class_basename($model),
                "restore_{$modelName}" => "Восстановление " . class_basename($model),
                "restore_any_{$modelName}" => "Массовое восстановление " . class_basename($model),
                "force_delete_{$modelName}" => "Принудительное удаление " . class_basename($model),
                "force_delete_any_{$modelName}" => "Массовое принудительное удаление " . class_basename($model),
                "replicate_{$modelName}" => "Копирование " . class_basename($model),
            ];
            
            foreach ($permissions as $name => $description) {
                if (!in_array($name, $existingPermissions)) {
                    Permission::create([
                        'name' => $name,
                        'guard_name' => 'web',
                        'description' => $description,
                    ]);
                    $this->line("✅ Создано: {$name}");
                    $created++;
                }
            }
        }
        
        $this->info("🎉 Создано {$created} новых разрешений!");
    }
}
