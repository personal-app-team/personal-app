<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class CleanActivityLogPermissions extends Command
{
    protected $signature = 'permissions:clean-activitylog';
    protected $description = 'Remove unwanted permissions for ActivityLog';

    public function handle()
    {
        $this->info('🧹 Очистка разрешений для ActivityLog...');
        
        // Разрешенные действия для ActivityLog
        $allowedActions = ['view_any', 'view'];
        
        // Получаем все разрешения для ActivityLog
        $activityPermissions = Permission::where('name', 'like', '%activity%')->get();
        
        $deletedCount = 0;
        foreach ($activityPermissions as $permission) {
            // Проверяем, содержит ли разрешение разрешенное действие
            $isAllowed = false;
            foreach ($allowedActions as $action) {
                if (str_contains($permission->name, $action)) {
                    $isAllowed = true;
                    break;
                }
            }
            
            // Если не разрешено - удаляем
            if (!$isAllowed) {
                // Отзываем у всех ролей
                foreach (\Spatie\Permission\Models\Role::all() as $role) {
                    $role->revokePermissionTo($permission);
                }
                // Удаляем разрешение
                $permission->delete();
                $deletedCount++;
                $this->line("🗑️  Удалено: {$permission->name}");
            }
        }
        
        $this->info("✅ Удалено {$deletedCount} ненужных разрешений ActivityLog");
        
        // Создаем нужные разрешения, если их нет
        $neededPermissions = [
            'view_any_activity_logs',
            'view_activity_logs',
        ];
        
        foreach ($neededPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
            $this->line("✅ Создано/проверено: {$permissionName}");
        }
    }
}
