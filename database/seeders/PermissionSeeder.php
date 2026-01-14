<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🛡️  Начинаем актуализацию разрешений...');

        // 1. Сохраняем текущие имена разрешений (до генерации)
        $oldPermissionNames = Permission::pluck('name')->toArray();
        
        // 2. Сохраняем текущие состояния ролей (для восстановления кроме admin)
        $roleStates = $this->saveRoleStates();
        
        // 3. Бэкап существующих политик перед генерацией
        $existingPolicies = $this->backupExistingPolicies();
        
        // 4. Генерируем разрешения через Shield с исправлением путей
        $this->command->info('📋 Запуск исправленной генерации политик...');
        Artisan::call('shield:generate-correct');
        $this->command->info('✅ Политики сгенерированы и пути исправлены');

        // 5. Восстанавливаем политики, которые были изменены вручную
        $this->restoreManualPolicies($existingPolicies);

        // 6. ✅ ДОБАВЛЯЕМ КАСТОМНЫЕ РАЗРЕШЕНИЯ
        $this->addCustomPermissions();

        // 7. Определяем НОВЫЕ разрешения (которые появились после генерации)
        $newPermissions = Permission::whereNotIn('name', $oldPermissionNames)->get();
        
        // 8. Восстанавливаем состояния ролей (кроме admin)
        $this->restoreRoleStates($roleStates);
        
        // 9. Админу даем ВСЕ разрешения (включая новые)
        $this->giveAdminAllPermissions();
        
        // 10. Выводим отчет о новых разрешениях
        $this->showNewPermissionsReport($newPermissions, $roleStates);
        
        // 11. Статистика
        $this->showStatistics();
    }
    
    private function saveRoleStates(): array
    {
        $roleStates = [];
        $roles = Role::with('permissions')->get();

        foreach ($roles as $role) {
            // Сохраняем все роли, но admin будем обрабатывать отдельно
            $roleStates[$role->name] = $role->permissions->pluck('name')->toArray();
        }

        return $roleStates;
    }
    
    private function backupExistingPolicies(): array
    {
        $policyPath = base_path('app/Policies');
        $backup = [];
        
        if (File::exists($policyPath)) {
            $files = File::files($policyPath);
            
            foreach ($files as $file) {
                $filename = $file->getFilename();
                $backup[$filename] = File::get($file->getPathname());
            }
        }
        
        $this->command->info("📁 Создан бэкап политик: " . count($backup) . " файлов");
        return $backup;
    }
    
    private function restoreManualPolicies(array $existingPolicies): void
    {
        $policyPath = base_path('app/Policies');
        
        // Список политик, которые мы изменяли вручную
        $manualPolicies = [
            'AssignmentPolicy.php',
            'DatabaseNotificationPolicy.php',
            // Добавьте другие политики, которые изменяли вручную
        ];
        
        $restoredCount = 0;
        foreach ($manualPolicies as $policy) {
            if (isset($existingPolicies[$policy])) {
                File::put($policyPath . '/' . $policy, $existingPolicies[$policy]);
                $this->command->info("✅ Восстановлена ручная политика: {$policy}");
                $restoredCount++;
            }
        }
        
        if ($restoredCount > 0) {
            $this->command->info("📋 Всего восстановлено ручных политик: {$restoredCount}");
        }
    }

    private function addCustomPermissions(): void
    {
        $this->command->info('➕ Добавляем кастомные разрешения...');
        
        $customPermissions = [
            'confirm_assignment',
            'reject_assignment',
            'create_brigadier_schedule',
            'view_activity_logs',
        ];
        
        foreach ($customPermissions as $permissionName) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }
        
        $this->command->info('✅ Кастомные разрешения добавлены');
    }
    
    private function restoreRoleStates(array $roleStates): void
    {
        $this->command->info('🔄 Восстанавливаем сохраненные назначения ролей (кроме admin)...');
        
        $restoredCount = 0;
        foreach ($roleStates as $roleName => $permissionNames) {
            // Пропускаем admin - он получит все разрешения отдельно
            if ($roleName === 'admin') continue;
            
            $role = Role::where('name', $roleName)->first();
            if (!$role) continue;
            
            // Находим разрешения, которые все еще существуют после генерации
            $existingPermissions = Permission::whereIn('name', $permissionNames)->get();
            
            if ($existingPermissions->count() > 0) {
                // Синхронизируем ТОЛЬКО существующие разрешения
                $role->syncPermissions($existingPermissions);
                $this->command->info("✅ Роли '{$roleName}' восстановлено {$existingPermissions->count()} разрешений");
                $restoredCount += $existingPermissions->count();
            }
        }
        
        $this->command->info("📊 Всего восстановлено: {$restoredCount} разрешений для всех ролей (кроме admin)");
    }
    
    private function giveAdminAllPermissions(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        
        if (!$adminRole) return;
        
        $allPermissions = Permission::all();
        $adminRole->syncPermissions($allPermissions);
        
        $this->command->info("🎯 Роли 'admin' назначено {$allPermissions->count()} разрешений");
    }
    
    private function showNewPermissionsReport($newPermissions, $roleStates): void
    {
        if ($newPermissions->count() > 0) {
            $this->command->info("\n🔔 ВНИМАНИЕ: Обнаружены новые разрешения!");
            $this->command->info("   Разрешения автоматически назначены ТОЛЬКО для роли 'admin'.");
            $this->command->info("   Для других ролей назначьте их вручную через панель Shield.");
            
            $this->command->info("\n📋 Список новых разрешений:");
            foreach ($newPermissions as $permission) {
                $this->command->info("   • {$permission->name}");
            }
            
            $this->command->info("\n📊 Состояние ролей после восстановления:");
            foreach ($roleStates as $roleName => $permissionNames) {
                if ($roleName !== 'admin') {
                    $role = Role::where('name', $roleName)->first();
                    if ($role) {
                        $this->command->info("   - {$roleName}: {$role->permissions->count()} разрешений");
                    }
                }
            }
            
            $this->command->info("\n💡 Совет: Для назначения новых разрешений ролям:");
            $this->command->info("   1. Перейдите в панель: Shield → Роли");
            $this->command->info("   2. Выберите роль и отредактируйте");
            $this->command->info("   3. Назначьте нужные разрешения");
            
            // Группировка по сущностям для удобства
            $this->command->info("\n🔍 Группировка новых разрешений по сущностям:");
            $groupedPermissions = [];
            foreach ($newPermissions as $permission) {
                $parts = explode('_', $permission->name, 2);
                if (count($parts) === 2) {
                    $entity = str_replace('::', '_', $parts[1]);
                    $action = $parts[0];
                    if (!isset($groupedPermissions[$entity])) {
                        $groupedPermissions[$entity] = [];
                    }
                    if (!in_array($action, $groupedPermissions[$entity])) {
                        $groupedPermissions[$entity][] = $action;
                    }
                }
            }
            
            foreach ($groupedPermissions as $entity => $actions) {
                $this->command->info("   - {$entity}: " . implode(', ', $actions));
            }
        } else {
            $this->command->info("\n✅ Новых разрешений не обнаружено.");
        }
    }
    
    private function showStatistics(): void
    {
        $this->command->info("\n📊 Итоговая статистика:");
        
        $roles = Role::withCount('permissions')->orderBy('name')->get();
        foreach ($roles as $role) {
            $this->command->info("   - {$role->name}: {$role->permissions_count} разрешений");
        }
        
        $permissionCount = Permission::count();
        $this->command->info("\n📈 Всего разрешений в системе: {$permissionCount}");
        
        // Дополнительная информация о политиках
        $policyPath = base_path('app/Policies');
        if (File::exists($policyPath)) {
            $policyFiles = File::files($policyPath);
            $this->command->info("📁 Всего политик: " . count($policyFiles));
        }
    }
}
