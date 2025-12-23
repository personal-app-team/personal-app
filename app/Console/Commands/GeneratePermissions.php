<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\File;

class GeneratePermissions extends Command
{
    protected $signature = 'permissions:generate';
    protected $description = 'Генерирует базовые разрешения для всех моделей';

    public function handle()
    {
        $models = $this->getModels();
        $permissions = [];
        
        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'];
        
        foreach ($models as $model) {
            $modelName = strtolower(class_basename($model));
            
            // Определяем группу на основе модели
            $group = $this->getGroupForModel($modelName);
            
            foreach ($actions as $action) {
                $permissionName = "{$action}_{$modelName}";
                $description = $this->getDescriptionForPermission($action, $modelName);
                
                $permissions[] = [
                    'name' => $permissionName,
                    'group' => $group,
                    'description' => $description,
                    'guard_name' => 'web',
                ];
            }
            
            // Дополнительные специфичные разрешения
            $specificPermissions = $this->getSpecificPermissionsForModel($modelName);
            foreach ($specificPermissions as $specific) {
                $permissions[] = [
                    'name' => $specific['name'],
                    'group' => $group,
                    'description' => $specific['description'],
                    'guard_name' => 'web',
                ];
            }
        }
        
        // Общие системные разрешения
        $systemPermissions = [
            ['name' => 'access_panel', 'group' => 'system', 'description' => 'Доступ к админ-панели'],
            ['name' => 'manage_settings', 'group' => 'system', 'description' => 'Управление настройками'],
            ['name' => 'view_reports', 'group' => 'report', 'description' => 'Просмотр отчетов'],
            ['name' => 'export_data', 'group' => 'system', 'description' => 'Экспорт данных'],
            ['name' => 'import_data', 'group' => 'system', 'description' => 'Импорт данных'],
        ];
        
        foreach ($systemPermissions as $perm) {
            $permissions[] = $perm + ['guard_name' => 'web'];
        }
        
        // Создаем или обновляем разрешения
        $created = 0;
        $updated = 0;
        
        foreach ($permissions as $permissionData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
            
            if ($permission->wasRecentlyCreated) {
                $created++;
            } else {
                // Обновляем описание, если оно изменилось
                if ($permission->description !== $permissionData['description'] || 
                    $permission->group !== $permissionData['group']) {
                    $permission->update([
                        'description' => $permissionData['description'],
                        'group' => $permissionData['group']
                    ]);
                    $updated++;
                }
            }
        }
        
        $this->info("Создано новых разрешений: {$created}");
        $this->info("Обновлено разрешений: {$updated}");
        $this->info("Всего разрешений в системе: " . Permission::count());
    }
    
    private function getModels(): array
    {
        $models = [];
        $modelPath = app_path('Models');
        
        $files = File::allFiles($modelPath);
        
        foreach ($files as $file) {
            $model = 'App\\Models\\' . $file->getFilenameWithoutExtension();
            if (class_exists($model)) {
                $models[] = $model;
            }
        }
        
        return $models;
    }
    
    private function getGroupForModel(string $modelName): string
    {
        $map = [
            'user' => 'user',
            'assignment' => 'assignment',
            'shift' => 'shift',
            'workrequest' => 'work_request',
            'expense' => 'expense',
            'compensation' => 'compensation',
            'candidate' => 'candidate',
            'vacancy' => 'vacancy',
            'recruitmentrequest' => 'recruitment',
            'interview' => 'recruitment',
            'hiringdecision' => 'recruitment',
            'department' => 'department',
            'contractor' => 'contractor',
            'project' => 'project',
            'purpose' => 'purpose',
            'address' => 'address',
            'category' => 'category',
            'specialty' => 'specialty',
        ];
        
        return $map[$modelName] ?? 'system';
    }
    
    private function getDescriptionForPermission(string $action, string $model): string
    {
        $actionMap = [
            'view_any' => 'Просмотр всех',
            'view' => 'Просмотр',
            'create' => 'Создание',
            'update' => 'Редактирование',
            'delete' => 'Удаление',
            'restore' => 'Восстановление',
            'force_delete' => 'Полное удаление',
        ];
        
        $modelMap = [
            'user' => 'пользователей',
            'assignment' => 'назначений',
            'shift' => 'смен',
            'workrequest' => 'заявок на работы',
            'expense' => 'расходов',
            'compensation' => 'компенсаций',
            'candidate' => 'кандидатов',
            'vacancy' => 'вакансий',
            'recruitmentrequest' => 'заявок на подбор',
            'interview' => 'собеседований',
            'hiringdecision' => 'решений о найме',
        ];
        
        $actionText = $actionMap[$action] ?? ucfirst($action);
        $modelText = $modelMap[$model] ?? $model;
        
        return "{$actionText} {$modelText}";
    }
    
    private function getSpecificPermissionsForModel(string $modelName): array
    {
        $specifics = [
            'assignment' => [
                ['name' => 'confirm_assignment', 'description' => 'Подтверждение назначения'],
                ['name' => 'reject_assignment', 'description' => 'Отклонение назначения'],
                ['name' => 'cancel_assignment', 'description' => 'Отмена назначения'],
            ],
            'shift' => [
                ['name' => 'start_shift', 'description' => 'Начало смены'],
                ['name' => 'end_shift', 'description' => 'Завершение смены'],
                ['name' => 'approve_shift', 'description' => 'Утверждение смены'],
            ],
            'workrequest' => [
                ['name' => 'publish_workrequest', 'description' => 'Публикация заявки'],
                ['name' => 'take_workrequest', 'description' => 'Взятие заявки в работу'],
            ],
        ];
        
        return $specifics[$modelName] ?? [];
    }
}
