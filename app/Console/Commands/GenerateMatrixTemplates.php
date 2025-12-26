<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateMatrixTemplates extends Command
{
    protected $signature = 'matrix:templates';
    protected $description = 'Создать шаблоны CSV для матрицы доступа';

    public function handle()
    {
        // 1. Создаем матрицу доступа
        $resources = $this->getResourcesList();
        
        $roles = ['admin', 'initiator', 'dispatcher', 'executor', 'contractor_admin', 
                 'contractor_dispatcher', 'contractor_executor', 'hr', 'manager', 'trainee', 'viewer'];
        
        $csv = "Resource,Model," . implode(',', $roles) . ",notes\n";
        
        foreach ($resources as $resource => $model) {
            $csv .= "{$resource},{$model}," . str_repeat('❌,', count($roles)) . "\n";
        }
        
        File::put('docs/access_matrix.csv', $csv);
        $this->info("✅ Создан шаблон матрицы доступа: docs/access_matrix.csv");
        
        // 2. Создаем таблицу ограниченного доступа
        $limitedCsv = "Resource,Role,Custom Permissions (через запятую)\n";
        $limitedCsv .= "# Пример: AssignmentResource,executor,view_own_assignment,confirm_assignment\n";
        $limitedCsv .= "# Пример: WorkRequestResource,dispatcher,publish_workrequest,take_workrequest\n";
        
        File::put('docs/limited_access.csv', $limitedCsv);
        $this->info("✅ Создан шаблон таблицы ограниченного доступа: docs/limited_access.csv");
        
        $this->info("\n🎯 Инструкция:");
        $this->info("1. Заполните docs/access_matrix.csv (❌, 👁️, ✅, 🔐)");
        $this->info("2. Для каждой 🔐 в docs/limited_access.csv укажите кастомные разрешения");
        $this->info("3. Запустите: sail artisan permissions:refresh");
        $this->info("4. Скопируйте сгенерированный массив в RoleSeeder");
        $this->info("5. Запустите: sail artisan db:seed --class=DatabaseSeeder");
    }
    
    private function getResourcesList(): array
    {
        $files = glob(app_path('Filament/Resources/*Resource.php'));
        $resources = [];
        
        foreach ($files as $file) {
            $resourceName = basename($file, '.php');
            $className = 'App\\Filament\\Resources\\' . $resourceName;
            
            if (class_exists($className)) {
                try {
                    $model = $className::getModel();
                    $modelName = strtolower(class_basename($model));
                } catch (\Exception $e) {
                    $modelName = 'unknown';
                }
                
                $resources[$resourceName] = $modelName;
            }
        }
        
        return $resources;
    }
}
